<?php

use Books\Book\Models\Cycle;
use Books\Comments\Models\Comment;
use Books\Reviews\Models\Review;
use Books\User\Models\Settings;

return [
    'slavable' => [
        Review::class,
        Settings::class,
        Comment::class,
        Cycle::class,
        \Books\Notifications\Models\Notification::class,
    ],
];
