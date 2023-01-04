<?php

namespace Books\Book\Classes;

use Books\Book\Models\Book;

class BookEventHandler
{
    public function __construct(protected BookManager $manager = new BookManager())
    {

    }

    public function afterCreate(Book $book): void
    {
        $this->manager->setDefaultCover($book);
    }

    public function afterChaptersUpdate(Book $book): void
    {
        $this->manager->countContentLength($book);
    }
}
