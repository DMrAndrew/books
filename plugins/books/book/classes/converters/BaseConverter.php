<?php

namespace Books\Book\Classes\Converters;

use Books\Book\Models\Book;
use System\Models\File;

class BaseConverter
{
    public File $file;
    public function __construct(public Book $book)
    {
        $this->file = new File();
    }

    public function make(): File
    {
        return File::find(6923);
    }
}
