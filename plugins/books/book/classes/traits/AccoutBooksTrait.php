<?php

namespace Books\Book\Classes\Traits;

use Books\Book\Models\Author;
use Books\Book\Models\Book;
use October\Rain\Database\Collection;

trait AccoutBooksTrait
{
    /**
     * @return Collection
     */
    private function getAccountBooks(): Collection
    {
        $allAccountProfilesIds = $this->user->profiles->pluck('id')->toArray();
        $booksIds = Author
            ::with(['book'])
            ->whereIn('profile_id', $allAccountProfilesIds)
            ->get()
            ->pluck('book_id')
            ->toArray();

        return Book::whereIn('id', $booksIds)->get();
    }
}
