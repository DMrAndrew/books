<?php

namespace Books\User\Updates;

use Illuminate\Support\Facades\DB;
use October\Rain\Database\Updates\Seeder;

class SeedCountry extends Seeder
{
    public function run()
    {
        $list = [
            ['name' => 'Российская федерация','code' => 'ru']
        ];
        DB::table('books_user_countries')->insert($list);
    }
}
