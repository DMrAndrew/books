<?php

use Books\Profile\Models\ProfileSettings;
use Books\Reviews\Models\Review;
use Mobecan\Favorites\Models\Favorite;

return [
    'profileable' => [
        Review::class,
        Favorite::class,
        ProfileSettings::class,
    ]
];
