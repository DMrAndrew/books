<?php

namespace Books\Book\Classes\Services;

use Books\Book\Models\Book;
use Books\Book\Models\CoAuthor;
use Books\Book\Models\Tag;
use Books\Catalog\Models\Genre;
use Db;
use Event;
use Illuminate\Support\Collection;
use RainLab\User\Models\User;

class UpdateBookService extends BookService
{
    protected $coAuthors = null;
    protected $genres = null;
    protected $tags = null;


    private function postCoauthors(): \October\Rain\Support\Collection|Collection
    {
        return collect(post('coauthors'))->map(function ($item) {
            return $this->toCoAuthorItem($item['user_id'], $item['percent_value']);
        }) ?? new Collection();
    }

    private function postGenres()
    {
        return Genre::query()->whereIn('id', collect(post('genres') ?? [])->pluck('id'))->get();
    }

    private function postTags()
    {
        return $this->user->tags()->whereIn('id', collect(post('tags') ?? [])->pluck('id'))->get();
    }

    private function toCoAuthorItem(int $user_id, int $percent = 0)
    {
        $user = User::find($user_id);
        $user->pivot = new CoAuthor(['user_id' => $user_id, 'percent' => $percent]);
        $user->pivot->owner = $user->id === $this->user->id;
        return $user;
    }


    /**
     * @param array $data
     * @return Book
     */
    public function save(array $data): Book
    {
        Db::transaction(function () use ($data) {

            $this->book->update($data);

            $this->book->coauthors()->sync($this->getCoAuthors()->pluck('id')->toArray());
            $items = $this->book->coauthors()->get();
            $this->getCoAuthors()->whereIn('id', $items->pluck('id'))->each(function ($item) {
                $this->book->coauthors()->updateExistingPivot($item->id, ['percent' => $item->pivot->percent]);

            });

            $tags = $this->book->tags()->get();
            $this->getTags()->diff($tags)->each(fn($i) => $this->book->tags()->add($i));
            $tags->diff($this->getTags())->each(fn($i) => $this->book->tags()->remove($i));

            $genres = $this->book->genres()->get();
            $this->getGenres()->diff($genres)->each(fn($i) => $this->book->genres()->add($i));
            $genres->diff($this->getGenres())->each(fn($i) => $this->book->genres()->remove($i));

            Event::fire('books.book.updated', [$this->book]);

            $this->book->save(null, $this->getSessionKey());
        });
        return $this->book;
    }

    /**
     * @param int $user_id
     * @param int $value
     * @return mixed
     */
    public function modifyCoAuthorPercent(int $user_id, int $value)
    {
        $items = $this->getCoAuthors();
        if ($item = $items->first(fn($i) => $i->id === $user_id)) {
            $item->pivot = ['percent' => $value];
        }
        $this->coAuthors = $items;
    }

    /**
     * @param Tag $tag
     * @return mixed
     */
    public function addTag(Tag $tag)
    {
        $this->getTags()->push($tag);
    }

    /**
     * @param Genre $genre
     * @return void
     */
    public function addGenre(Genre $genre)
    {
        $this->getGenres()->push($genre);
    }

    /**
     * @param User $coAuthor
     * @return mixed
     */
    public function addCoAuthor(User $coAuthor)
    {
        if (!$this->getCoAuthors()->count()) {
            $this->coAuthors->push($this->toCoAuthorItem($this->user->id, 100));
        }
        $this->coAuthors->push($this->toCoAuthorItem($coAuthor->id));
    }

    /**
     * @param $pivot
     * @return mixed
     */
    public function getCoAuthors($pivot = false)
    {
        if (is_null($this->coAuthors)) {
            $this->coAuthors = $this->postCoauthors();
        }
        return $this->coAuthors;
    }

    /**
     * @return Collection
     */
    public function getGenres(): Collection
    {
        if (is_null($this->genres)) {
            $this->genres = $this->postGenres();
        }
        return $this->genres;
    }

    /**
     * @return Collection
     */
    public function getTags(): Collection
    {
        if (is_null($this->tags)) {
            $this->tags = $this->postTags();
        }
        return $this->tags;
    }

    /**
     * @param Genre|null $genre
     * @return mixed
     */
    public function removeGenre(Genre $genre)
    {
        $key = $this->getGenres()->search(fn($i) => $i->id === $genre->id);
        if (is_int($key)) {
            $this->getGenres()->forget($key);
        }
    }

    /**
     * @param Tag $tag
     * @return bool
     */
    public function removeTag(Tag $tag)
    {
        $key = $this->getTags()->search(fn($i) => $i->id === $tag->id);
        if (is_int($key)) {
            $this->getTags()->forget($key);
        }
    }

    /**
     * @param User|null $author
     * @return mixed
     */
    public function removeCoAuthor(User $author)
    {
        $key = $this->getCoAuthors()->search(fn($i) => $i->id === $author->id);
        if (is_int($key)) {
            $this->getCoAuthors()->forget($key);
        }
        if ($this->getCoAuthors()->count() === 1) {
            $this->coAuthors = new Collection();
        }
    }
}
