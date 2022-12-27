<?php

namespace Books\Book\Classes;

use Books\Book\Models\Book;

class BookEventHandler
{
    public function afterCreate(Book $book){
        $manager =  (new BookManager());
        $manager->countContentLength($book);
        $manager->setDefaultCover($book);
    }
}
