<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Jobs\GenreRating;
use Books\Book\Models\Book;
use Books\Book\Models\BookGenre;
use Books\Catalog\Models\Genre;
use Db;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Queue;

class GenreRater implements ShouldQueue, ShouldBeUnique
{
    public function compute()
    {
        Genre::query()
            ->whereHas('books', fn($books) => $books->onlyPublicStatus())
            ->with(['books' => fn($books) => $books->onlyPublicStatus(), 'books.ebook', 'books.stats'])
            ->get()
            ->map(function ($genre) {
                $rating = $genre->books?->sortByDesc(fn(Book $book) => $book->stats->collected_genre_rate)
                    ->values();

                Db::transaction(function () use ($rating, $genre) {
                    BookGenre::query()->where('genre_id', $genre->id)->update(['rate_number' => null]);
                    if ($rating->count()) {
                        BookGenre::query()->upsert(
                            $rating->map(fn($book, $key) => array_merge($book->pivot->only(['book_id', 'genre_id']), ['rate_number' => $key + 1]))
                                ->toArray(),
                            ['book_id', 'genre_id'],
                            ['rate_number']);
                    }
                });
            });
    }

    public static function queue(): void
    {
        Queue::push(GenreRating::class);
    }
}
