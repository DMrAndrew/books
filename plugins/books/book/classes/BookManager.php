<?php

namespace Books\Book\Classes;

use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use System\Models\File;
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

    /**
     * Try set default book cover if not exists one.
     *
     * @param Book $book
     * @return void
     */
    public function setDefaultCover(Book $book): void
    {
        if (!$book->cover) {
            if ($dir = config('book.book_cover_blank_dir')) {
                $file_src = collect(glob(base_path() . "/$dir/*.png"))->random();
                if(file_exists($file_src)){
                    $file = (new File())->fromFile($file_src);
                    $file->is_public = true;
                    $file->save();
                    $book->cover()->add($file);
                }
            }
        }
    }
}
