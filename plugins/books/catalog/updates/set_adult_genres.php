<?php

namespace Books\Catalog\Updates;

use Books\Catalog\Models\Genre;
use October\Rain\Database\Updates\Seeder;

class SetAdultGenres extends Seeder
{
    public function run()
    {
        if ($adult = Genre::query()->roots()->name('18+')->first()) {
            $adult->checkAdult();
            $adult->children->each->checkAdult();
        }
    }
}
