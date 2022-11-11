<?php

use RainLab\Blog\Models\Post;
use Books\Reviews\Models\Review;
use Mobecan\Favorites\Models\Favorite;

return [
    'profileable' => [
        Review::class,
        Post::class,
        Favorite::class,
    ]
];
