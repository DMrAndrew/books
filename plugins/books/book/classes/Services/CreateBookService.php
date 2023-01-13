<?php

namespace Books\Book\Classes\Services;

use Event;
use ValidationException;
use Books\Book\Models\Tag;
use Books\Book\Models\Book;
use RainLab\User\Models\User;
use Books\Book\Models\CoAuthor;
use Books\Catalog\Models\Genre;
use Illuminate\Support\Collection;

class CreateBookService extends BookService
{

    /**
     * @return Book
     * @throws ValidationException
     */
    public function save(array $data): Book
    {
        if(isset($data['cycle_id'])){
            $data['cycle_id'] = $this->user->cycles()->find($data['cycle_id'])?->id ?? null;
        }
        $book = new Book($data);
        $book->user = $this->user;
        $book->save(null, $this->getSessionKey());
        Event::fire('books.book.created', [$book]);

        return $book;
    }

    public function modifyCoAuthorPercent(?int $user_id, int $value)
    {
        if (!$user_id) {
            return;
        }
        if ($author = $this->book->getDiffered($this->getSessionKey())
            ->where('master_field', '=', 'coauthors')
            ->first(fn($bind) => $bind->slave_id === $user_id)) {
            $author->pivot_data = ['percent' => $value];
            $author->save();
        }
    }

    /**
     * @param Tag $tag
     * @return bool
     */
    public function addTag(Tag $tag): bool
    {
        $this->book->tags()->add($tag, $this->getSessionKey());
        return true;
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function addGenre(Genre $genre)
    {
        $this->book->genres()->add($genre, $this->getSessionKey());
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function addCoAuthor(User $user)
    {
        if (!$this->getCoAuthors()->count()) {
            $this->book->coauthors()->add($this->user, $this->getSessionKey(), ['percent' => 100]);
        }
        $this->book->coauthors()->add($user, $this->getSessionKey(), ['percent' => 0]);
    }

    /**
     * @return mixed
     */
    public function getCoAuthors($pivot = false)
    {
        $coauthors = $this->book->coauthors()->withDeferred($this->getSessionKey())->get();
        if ($pivot) {
            $this->book->getDiffered($this->getSessionKey())
                ->where('master_field', '=', 'coauthors')
                ->each(function ($bind) use ($coauthors) {
                    if ($author = $coauthors->first(fn($i) => $i->id === $bind->slave_id)) {
                        $author->pivot = new CoAuthor(['user_id' => $author->id] + ($bind->pivot_data ?? []));
                    }
                    if ($bind->slave_id === $this->user->id) {
                        $author->pivot->owner = true;
                    }
                });
        }

        return $coauthors;
    }

    public function getGenres(): Collection
    {
        return $this->book->genres()->withDeferred($this->getSessionKey())->get();
    }

    public function getTags(): Collection
    {
        return $this->book?->tags()->withDeferred($this->getSessionKey())->get();
    }

    /**
     * @param Genre $genre
     * @return null
     */
    public function removeGenre(?Genre $genre)
    {
        $this->book->genres()->remove($genre, $this->getSessionKey());
    }

    public function removeTag(Tag $tag): bool
    {
        $this->book?->tags()->remove($tag, $this->getSessionKey());
        return true;
    }

    public function removeCoAuthor(?User $author)
    {
        $this->book->coauthors()->remove($author, $this->getSessionKey());
        if ($this->getCoAuthors()->count() === 1) {
            $this->book->coauthors()->remove($this->user, $this->getSessionKey());
        }
    }
}
