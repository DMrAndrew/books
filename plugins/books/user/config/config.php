<?php

use Books\Book\Models\Book;
use Books\Profile\Models\Profile;

return [
    'searchable' => [
        'books' => Book::class,
        'authors' => Profile::class
    ]
];
