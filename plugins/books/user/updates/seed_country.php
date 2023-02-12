<?php

namespace Books\User\Updates;

use Books\User\Models\Country;
use Illuminate\Support\Facades\DB;
use Monarobase\CountryList\CountryListFacade;
use October\Rain\Database\Updates\Seeder;

class SeedCountry extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            if ($ru = Country::code('ru')->first()) {
                $ru->update(['code' => strtoupper($ru->code), ['force' => true]]);
            }
            $exists = Country::all()
                ->pluck('code')
                ->toArray();

            $list = collect(CountryListFacade::getList('ru'))
                ->map(fn ($i, $k) => ['name' => $i, 'code' => $k])
                ->values()
                ->filter(fn ($i) => ! in_array($i['code'], $exists));

            if ($list->count()) {
                DB::table('books_user_countries')
                    ->insert($list->toArray());
            }

            $prefers = ['RU', 'UA', 'US', 'GB'];
            $preferred = Country::code(...$prefers)->get();
            $preferred->each(fn ($i) => $i->update(['sort_order' => array_search($i->code, $prefers)], ['force' => true]));

            Country::all()
                ->filter(fn ($i) => ! in_array($i->code, $prefers))
                ->each
                ->update(['sort_order' => count($prefers)], ['force' => true]);
        });
    }
}
