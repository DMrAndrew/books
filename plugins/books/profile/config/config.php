<?php

use Books\Book\Models\AwardBook;
use Books\Book\Models\Cycle;
use Books\Comments\Models\Comment;
use Books\Reviews\Models\Review;
use Books\User\Models\Settings;
use Books\Blog\Models\Post;

return [
    'slavable' => [
        Review::class,
        Settings::class,
        Comment::class,
        Cycle::class,
        Post::class,
        AwardBook::class
    ],
];
