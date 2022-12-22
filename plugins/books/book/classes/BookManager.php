<?php

namespace Books\Book\Classes;

use Books\Book\Models\Book;
use Books\Book\Models\Chapter;

class BookManager
{
    public function countContentLength(Chapter|Book $item): void
    {
        if ($item instanceof Chapter) {
            $this->countChapterLength($item);
        } else {
            $item->chapters->each(fn($i) => $this->countChapterLength($i));
        }

        $this->countBookLength($item instanceof Chapter ? $item->book : $item);
    }

    protected function countBookLength(Book $book): void
    {
        $book->length = $book->chapters->pluck('length')->sum();
        $book->save();
    }

    protected function countChapterLength(Chapter $chapter): void
    {
        $chapter->length = strlen(strip_tags(preg_replace('/\s+/', '', $chapter->content)));
        $chapter->save();

    }
}
