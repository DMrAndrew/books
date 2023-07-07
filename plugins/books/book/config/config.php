<?php

use Books\Book\Models\Book;
use Books\Catalog\Models\Genre;

return [
    'book_cover_blank_dir' => '/themes/demo/assets/images/book-cover-blank/',
    'prohibited' => ['Жанр' => Genre::class, 'Книга' => Book::class],
    'annotation_length' => env('BOOKS_ANNOTATION_LENGTH', 300),
    'minimal_price' => env('EDITION_MINIMAL_PRICE', 30),
    'minimal_free_parts' => env('EDITION_MINIMAL_FREE_PARTS', 3),
];
