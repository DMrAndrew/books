<?php

namespace Books\Book\Classes;

use Books\Book\Models\Book;
use Books\Catalog\Models\Genre;

class GenreManager
{
    public function assign(Book $book, Genre $genre)
    {
        if ($book->genres()->count() === 4) {
            throw new \ValidationException('Можно добавить не более 4-х соавторов.');
        }
        return $book->genres()->add($genre);
    }
}
