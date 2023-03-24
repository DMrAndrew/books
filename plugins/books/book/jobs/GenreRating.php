<?php

namespace Books\Book\Jobs;

use Books\Book\Classes\GenreRater;
use Books\Book\Models\Book;
use Exception;
use Log;

class GenreRating
{
    public function fire($job, $data)
    {
        try {
            (new Book())->rater()->setWithDump(true)->applyAllBook();
            (new GenreRater())->compute();
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            throw $exception;
        }
    }
}
