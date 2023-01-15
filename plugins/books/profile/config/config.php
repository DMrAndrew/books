<?php


use Books\Reviews\Models\Review;
use Mobecan\Favorites\Models\Favorite;
use Books\Profile\Models\ProfileSettings;

return [
    'profileable' => [
        Review::class,
        Favorite::class,
        ProfileSettings::class,
    ]
];
