<?php

namespace Books\Book\Classes;

use Db;
use Event;
use Session;
use ValidationException;
use Books\Book\Models\Tag;
use Books\Book\Models\Book;
use RainLab\User\Models\User;
use Books\Book\Models\Author;
use Books\Catalog\Models\Genre;
use Books\Profile\Models\Profile;
use Illuminate\Support\Collection;


class BookService
{
    protected Book $proxy;

    protected bool $bound = false;

    protected array $relations = ['profiles', 'genres', 'tags'];


    public function __construct(protected User $user, protected ?Book $book = null, protected ?string $session_key = null)
    {
        $this->proxy = new Book();
        $this->book ??= $this->proxy;
    }

    /**
     * @return string|null
     */
    public function getSessionKey(): ?string
    {
        return $this->session_key;
    }

    protected function isNew(): bool
    {
        return !$this->book->id;
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
     * @param array|null $data
     * @return Book
     * @throws ValidationException
     */
    public function save(?array $data = null): Book
    {
        return Db::transaction(function () use ($data) {
            $data = collect($data);

            $this->bindOwner();

            if ($this->getAuthors()->pluck(Author::PERCENT)->sum() !== 100) {
                throw  new ValidationException(['authors' => 'Сумма распределения процентов от продаж должна быть равна 100.']);
            }

            $bookData = $data->only($this->proxy->getFillable());

            if ($bookData->has('cycle_id')) {
                $bookData['cycle_id'] = $this->user->cycles()->find($bookData['cycle_id'])?->id ?? null;
            }

            $this->{($this->isNew() ? 'create' : 'update')}($bookData->toArray());
            $this->book->ebook?->update($data->only(['comment_allowed', 'download_allowed'])->toArray());

            $this->clean();

            return $this->book;

        });

    }

    protected function create(?array $data): Book
    {
        $this->book = new Book($data);
        $this->book->save(null, $this->getSessionKey());
        Event::fire('books.book.created', [$this->book]);

        return $this->book;
    }

    protected function update(?array $data): Book
    {
        $this->book->update($data);
        $this->syncRelations();
        $this->book->setSortOrder();
        Event::fire('books.book.updated', [$this->book]);

        return $this->book;
    }

    protected function syncRelations(): void
    {
        foreach ($this->relations as $relation) {
            $method = 'get' . ucfirst($relation);
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
        if (!$this->getOwnerProfile()) {
            $this->attachProfile($this->user->profile, [Author::PERCENT => 100, Author::IS_OWNER => 1]);
        }
    }


    public function setPercent(Profile $profile, int $value): void
    {
        if ($author = $this->proxy()->getDeferredAuthor($this->getSessionKey(), $profile)) {
            $author->pivot_data = array_replace($author->pivot_data, [Author::PERCENT => $value]);
            $author->save();
        }
    }

    /**
     * @param string|Tag|null $tag
     * @return void
     */
    public function addTag(null|string|Tag $tag): void
    {
        if (!$tag) {
            return;
        }
        $tag = $this->user->tags()->firstOrCreate([Tag::NAME => (is_string($tag) ? mb_ucfirst($tag) : $tag->{Tag::NAME})]);
        $this->proxy()->tags()->add($tag, $this->getSessionKey());
    }

    /**
     * @param Genre $genre
     * @return void
     */
    public function addGenre(Genre $genre): void
    {
        $this->proxy()->genres()->add($genre, $this->getSessionKey());
    }

    /**
     * @param Profile $profile
     * @return void
     */
    public function addProfile(Profile $profile): void
    {
        $this->bindOwner();
        $this->attachProfile($profile);

    }

    function attachProfile(Profile $profile, array $pivot = []): void
    {
        $this->proxy()->profiles()
            ->add($profile, $this->getSessionKey(),
                array_replace([
                    Author::PERCENT => 0,
                    Author::PROFILE_ID => $profile->id,
                    Author::IS_OWNER => false], $pivot)
            );
    }

    public function getAuthors(): Collection
    {
        return $this->getProfiles(true)->pluck('pivot');
    }

    protected function getOwnerProfile()
    {
        return $this->getProfiles()->first(fn($i) => $i->id === $this->user->profile->id);
    }

    /**
     * @return Collection
     */
    public function getProfiles($pivot = false): Collection
    {
        $authors = $this->proxy()->profiles()->withDeferred($this->getSessionKey())->get();
        if ($pivot) {
            $this->proxy()->getDeferredAuthors($this->getSessionKey())
                ->each(function ($bind) use ($authors) {
                    if ($author = $authors->first(fn($i) => $i->id === $bind->slave_id)) {
                        $author->pivot = new Author($bind->pivot_data ?? []);
                    }
                });
//        ->sortByDesc(fn ($a, $b) => $a->pivot['is_owner'] <=> $b->pivot['is_owner']) //TODO
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

    /**
     * @param Genre $genre
     * @return void
     */
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
