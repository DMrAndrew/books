<?php

namespace Books\Book\Updates;

use Books\Book\Classes\Enums\AwardsEnum;
use Books\Book\Models\Award;
use October\Rain\Database\Updates\Seeder;

class seed_awards_update_price extends Seeder
{
    public function run()
    {
        Award::where('type', AwardsEnum::USUAL)->update(['price' => 10]);
        Award::where('type', AwardsEnum::SILVER)->update(['price' => 15]);
        Award::where('type', AwardsEnum::GOLD)->update(['price' => 20]);
    }
}
