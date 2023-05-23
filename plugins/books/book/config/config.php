<?php

use Books\Book\Models\Book;
use Books\Catalog\Models\Genre;

return [
    'book_cover_blank_dir' => '/themes/demo/assets/images/book-cover-blank/',
    'prohibited' => ['Жанр' => Genre::class, 'Книга' => Book::class]
];
