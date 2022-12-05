<?php

use Books\Reviews\Models\Review;
use Mobecan\Favorites\Models\Favorite;

return [
    'profileable' => [
        Review::class,
        Favorite::class,
    ]
];
