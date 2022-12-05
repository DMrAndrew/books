<?php namespace Books\Book\Classes;

use Books\Book\Models\Book;
use Books\Book\Models\CoAuthor;
use RainLab\User\Models\User;

class CoAuthorManager
{
    public static function bindOnInsertCoAuthorEvent(): void
    {
        CoAuthor::creating(function (CoAuthor $pivot) {
            static::insertOwnerAsCoAuthor($pivot);
        });
    }

    protected static function insertOwnerAsCoAuthor(CoAuthor $pivot): void
    {
        if ((!$pivot->book->coauthors()->exists()) && $pivot->author_id !== $pivot->book->author_id) {
            $pivot->book->coauthors()->create(['author_id' => $pivot->book->author_id, 'percent' => 100]);
        }
    }

    public function assign(Book $book, User $author)
    {
        if ($book->coauthors()->count() === 4) {
            throw new \ValidationException('Можно добавить не более 3-х соавторов.');
        }
        return $book->coauthors()->add($author);
    }
}
