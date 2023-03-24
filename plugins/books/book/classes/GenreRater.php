<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Jobs\GenreRating;
use Books\Book\Models\Book;
use Books\Book\Models\BookGenre;
use Books\Catalog\Models\Genre;
use Db;
use Queue;

class GenreRater
{
    public function __construct()
    {
    }

    public function compute()
    {
        return Genre::query()
            ->with(['books', 'books.ebook', 'books.stats'])
            ->whereHas('books')
            ->get()
            ->map(function ($genre) {
                $books = $genre->books->sortByDesc(fn (Book $book) => $book->stats->forGenres($book->status === BookStatus::WORKING));

                Db::transaction(function () use ($books) {
                    $books->map->pivot->each->update(['rate_number' => null]);
                    BookGenre::query()->upsert(
                        $books->values()
                            ->map(fn ($book, $key) => array_merge($book->pivot->only(['book_id', 'genre_id']), ['rate_number' => $key + 1]))
                            ->toArray(),
                        ['book_id', 'genre_id'],
                        ['rate_number']);
                });
            });
    }

    public static function queue(): void
    {
        Queue::push(GenreRating::class);
    }
}
