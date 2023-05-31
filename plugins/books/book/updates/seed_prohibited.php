<?php

namespace Books\Book\Updates;

use Books\Book\Models\Prohibited;
use Books\Catalog\Models\Genre;
use RainLab\Location\Models\Country;
use Seeder;

class SeedProhibited extends Seeder
{
    public function run()
    {
        $genres = Genre::query()
            ->where('name', '=', 'Омегаверс')
            ->orWhere('name', '=', 'Фемслеш')
            ->orWhere('name', '=', 'Слеш')
            ->get();

        $country = Country::query()->code('RU')->first();
        foreach ($genres as $genre) {
            $prohibited = (new Prohibited([
                'country_id' => $country->id,
                'is_allowed' => false
            ]));
            $prohibited->prohibitable()->associate($genre);
            $prohibited->save();
        }
    }
}
