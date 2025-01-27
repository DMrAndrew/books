<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Classes\Enums\EditionsEnums;
use Books\Book\Classes\Exceptions\FBParserException;
use Books\Book\Classes\Exceptions\UnknownFormatException;
use Books\Book\Models\Author;
use Books\Book\Models\Book;
use Books\Book\Models\Edition;
use Books\Book\Models\Tag;
use Books\Catalog\Models\Genre;
use Books\Profile\Models\Profile;
use Carbon\Carbon;
use Db;
use Event;
use Exception;
use Illuminate\Support\Collection;
use Log;
use RainLab\User\Models\User;
use Session;
use System\Models\File;
use Tizis\FB2\Model\Book as TizisBook;
use ValidationException;

class BookService
{
    protected Book $proxy;

    protected bool $bound = false;

    protected array $relations = ['profiles', 'genres', 'tags'];

    public function __construct(protected User $user, protected ?Book $book = null, protected ?string $session_key = null)
    {
        $this->proxy = new Book();
        $this->book ??= new Book();
        $this->session_key ??= hash('xxh128', Carbon::now()->toISOString());
    }

    public function getSessionKey(): ?string
    {
        return $this->session_key;
    }

    protected function isNew(): bool
    {
        return ! $this->book->exists;
    }

    protected function isBounded(): bool
    {
        return $this->bound || $this->isNew() || Session::has($this->getSessionKey());
    }

    protected function bound(): bool
    {
        $this->bound = $this->isBounded() || Db::transaction(function () {
            foreach ($this->relations as $relation) {
                foreach ($this->book->$relation()->get() as $item) {
                    $this->proxy->$relation()->add($item, $this->getSessionKey(), $item->pivot ? $item->pivot->toArray() : []);
                }
            }
            Session::put($this->getSessionKey(), true);

            return true;
        });

        return $this->bound;
    }

    protected function proxy(): Book
    {
        $this->bound();

        return $this->proxy;
    }

    protected function clean(): void
    {
        $this->proxy->cancelDeferred($this->getSessionKey());
        Session::forget($this->getSessionKey());
        $this->bound = false;
    }

    /**
     * @throws FBParserException
     * @throws UnknownFormatException
     * @throws ValidationException
     */
    public function from(mixed $payload): Book
    {
        if (is_array($payload)) {
            $this->save($payload);

            return $this->book;
        }
        if ($payload instanceof File) {
            try {
                $tizisBook = (new FB2Manager($payload))->apply();
                $this->fromTizis($tizisBook);
                $payload->save();
                $ebook = $this->book->ebook()->first();

                if (! $ebook) {
                    $ebook = $this->book->editions()->save(Edition::make(['type' => EditionsEnums::default()]));
                }

                $ebook->fb2()->add($payload);
                $ebook->parseFB2($payload);
                Event::fire('books.book.parsed', [$this->book]);

                return $this->book;
            } catch (Exception $exception) {
                $this->book?->ebook?->setParsingFailed();
                throw $exception;
            }

        }
        throw new UnknownFormatException();
    }

    protected function fromTizis(TizisBook $book): Book
    {
        $info = $book->getInfo();
        $data = [
            'title' => strip_tags($info->getTitle()) ?: 'Без названия',
            'annotation' => $info->getAnnotation(),
            'status' => BookStatus::PARSING,
            'download_allowed' => false
        ];

        $this->book->removeValidationRule('cover', 'max:3072');
        if ($cover = $book->getCover()) {
            $cover = (new File())->fromData(base64_decode($cover), 'cover.jpg');
            $cover->save();
            $this->book->cover()->add($cover, $this->session_key);
        }

        collect(explode(',', $info->getKeywords()))->each(fn ($tag) => $this->addTag($tag));

        foreach ($info->getGenres() as $item) {
            if ($genre = Genre::query()->where('name', $item)->first()) {
                $this->addGenre($genre);
            }
        }
        $this->save($data);

        return $this->book;
    }

    /**
     * @throws ValidationException
     */
    protected function save(array $data = null): Book
    {
        return Db::transaction(function () use ($data) {
            $data = collect($data);

            $this->bindOwner();

            if ($this->getAuthors()->pluck(Author::PERCENT)->sum() !== 100) {
                throw new ValidationException(['authors' => 'Сумма распределения процентов от продаж должна быть равна 100.']);
            }

            $bookData = $data->only($this->proxy->getFillable());

            if ($bookData->has('cycle_id')) {
                $bookData['cycle_id'] = $this->user->profile->cyclesWithShared()->find($bookData->get('cycle_id'))?->id
                    ?? ($bookData->get('cycle_id') ? throw new ValidationException(['cycle_id' => 'Цикл не найден.']) : null);
            }

            $this->{($this->isNew() ? 'create' : 'update')}($bookData->toArray());
            $this->book->fresh();
            $ebook = $this->book->ebook()->first();
            $ebook?->fill($data->only(['comment_allowed', 'download_allowed', 'status'])->toArray());
            $ebook?->save();

            $this->clean();

            return $this->book;
        });
    }

    protected function create(?array $data): Book
    {
        $this->book = $this->book->fill($data);
        $this->book->save(null, $this->getSessionKey());
        Event::fire('books.book.created', [$this->book]);

        Log::info('try to notify on create');
        $coauthorsToNotify = $this->book->authors()->owner(false)->get(); Log::info($coauthorsToNotify);
        $this->notifyCoAuthors($coauthorsToNotify);

        return $this->book;
    }

    protected function update(?array $data): Book
    {
        // сохраним авторов до сохранения
        $authors = $this->book->authors;

        $this->book->update($data);
        $this->syncRelations();
        $this->book->setSortOrder();
        Event::fire('books.book.updated', [$this->book]);

        // получим авторов после сохранения, отсеивая старых
        $coauthorsToNotify = $this->book->authors()->get()->diff($authors);
        $this->notifyCoAuthors($coauthorsToNotify);

        return $this->book;
    }

    protected function notifyCoAuthors(Collection $coAuthors): void
    {
        if ($coAuthors->isEmpty()) {
            return;
        }

        $coAuthors->each(function (Author $coAuthor) {
            Event::fire('books.book::author.invited', [$coAuthor, $this->user->profile]);
        });
    }

    protected function syncRelations(): void
    {
        foreach ($this->relations as $relation) {
            $method = 'get'.ucfirst($relation);
            $this->book->$relation()->sync($this->{$method}());
        }

        $this->getProfiles(true)->intersect($this->book->profiles()->get())
            ->each(function ($profile) {
                $this->book->profiles()->updateExistingPivot($profile->id,
                    [Author::PERCENT => $profile->pivot?->percent ?? 0]
                );
            });
    }

    protected function bindOwner(): void
    {
        if (! $this->getOwnerProfile()) {
            $this->attachProfile($this->user->profile, [Author::PERCENT => 100, Author::IS_OWNER => 1, Author::ACCEPTED => 1]);
        }
    }

    public function setPercent(Profile $profile, int $value): void
    {
        if ($author = $this->proxy()->getDeferredAuthor($this->getSessionKey(), $profile)) {
            $author->pivot_data = array_replace($author->pivot_data, [Author::PERCENT => $value]);
            $author->save();
        }
    }

    public function addTag(null|string|Tag $tag): void
    {
        if (! $tag) {
            return;
        }
        $tag = Tag::query()->firstOrCreate([Tag::NAME => mb_ucfirst(trim((is_string($tag) ? ($tag) : $tag->{Tag::NAME})))]);
        $this->proxy()->tags()->add($tag, $this->getSessionKey());
    }

    public function addGenre(Genre $genre): void
    {
        $this->proxy()->genres()->add($genre, $this->getSessionKey());
    }

    public function syncGenres(Collection $list): void
    {
        foreach ($this->getGenres() as $genre) {
            $this->removeGenre($genre);
        }
        foreach ($list as $item) {
            $this->addGenre($item);
        }
    }

    public function addProfile(Profile $profile): void
    {
        $this->bindOwner();
        $this->attachProfile($profile);
    }

    protected function attachProfile(Profile $profile, array $pivot = []): void
    {
        $this->proxy()->profiles()
            ->add($profile, $this->getSessionKey(),
                array_replace([
                    Author::PERCENT => 0,
                    Author::PROFILE_ID => $profile->id,
                    Author::IS_OWNER => false,
                    Author::ACCEPTED => 0,
                ], $pivot)
            );
    }

    public function getAuthors(): Collection
    {
        return $this->getProfiles(true)->pluck('pivot');
    }

    protected function getOwnerProfile()
    {
        return $this->getProfiles()->first(fn ($i) => $i->id === $this->user->profile->id);
    }

    public function getProfiles($pivot = false): Collection
    {
        $authors = $this->proxy()->profiles()->withDeferred($this->getSessionKey())->get();
        if ($pivot) {
            $this->proxy()->getDeferredAuthors($this->getSessionKey())
                ->each(function ($bind) use ($authors) {
                    if ($author = $authors->first(fn ($i) => $i->id === $bind->slave_id)) {
                        $author->pivot = new Author($bind->pivot_data ?? []);
                    }
                });
        }

        return $authors;
    }

    public function getGenres(): Collection
    {
        return $this->proxy()->genres()->withDeferred($this->getSessionKey())->get();
    }

    public function getTags(): Collection
    {
        return $this->proxy()?->tags()->withDeferred($this->getSessionKey())->get()->sortBy(Tag::NAME);
    }

    public function removeGenre(Genre $genre): void
    {
        $this->proxy()->genres()->remove($genre, $this->getSessionKey());
    }

    public function removeTag(Tag $tag): void
    {
        $this->proxy()?->tags()->remove($tag, $this->getSessionKey());
    }

    public function removeProfile(Profile $profile): void
    {
        if (($this->getOwnerProfile()?->id) !== $profile->id) {
            $this->proxy()->profiles()->remove($profile, $this->getSessionKey());
        }
    }
}
