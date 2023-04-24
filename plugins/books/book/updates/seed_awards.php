<?php

namespace Books\Book\Updates;

use Books\Book\Classes\Enums\AwardsEnum;
use Books\Book\Models\Award;
use October\Rain\Database\Updates\Seeder;

class seed_awards extends Seeder
{
    public function run()
    {
        if (!Award::query()->count()) {
            collect([
                ['name' => 'Обычное перо', 'rate' => '3', 'price' => 5, 'type' => AwardsEnum::USUAL],
                ['name' => 'Серебрянное перо', 'rate' => '5', 'price' => 10, 'type' => AwardsEnum::SILVER],
                ['name' => 'Золотое перо', 'rate' => '10', 'price' => 15, 'type' => AwardsEnum::GOLD],
            ])->mapInto(Award::class)
                ->each
                ->save();
        }
    }
}
