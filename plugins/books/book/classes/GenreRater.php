<?php

namespace Books\Book\Classes;

use Books\Book\Jobs\GenreRaterExec;
use Books\Book\Jobs\RaterExec;
use Books\Book\Models\Book;
use Books\Book\Models\BookGenre;
use Books\Catalog\Models\Genre;
use Db;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Queue;

class GenreRater implements ShouldQueue, ShouldBeUnique
{
    public function compute(): void
    {
        Genre::query()
            ->whereHas('books', fn ($books) => $books->onlyPublicStatus())
            ->with(['books' => fn ($books) => $books->onlyPublicStatus(), 'books.ebook', 'books.stats'])
            ->cursor()
            ->each(fn ($genre) => $this->resort($genre));
    }

    protected function resort(Genre $genre): void
    {
        $sorted = $genre->books?->sortByDesc(fn (Book $book) => $book->stats->collected_genre_rate)->values() ?? collect(
        )->only();
        $reset = fn () => BookGenre::query()->where('genre_id', $genre->id)->update(['rate_number' => null]);
        $apply = function () use ($sorted, $reset) {
            $reset();
            if (! $sorted->count()) {
                return;
            }

            $values = $sorted->map(fn ($book, $key) => array_merge(
                $book->pivot->only(['book_id', 'genre_id']),
                ['rate_number' => $key + 1]
            )
            )->toArray();

            BookGenre::query()->upsert(
                $values,
                ['book_id', 'genre_id'],
                ['rate_number']
            );
        };

        Db::transaction($apply);
    }

    public static function queue(): void
    {
        Queue::push(GenreRaterExec::class);
    }
}
