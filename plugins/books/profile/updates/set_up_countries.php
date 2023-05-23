<?php

namespace Books\Profile\Updates;

use RainLab\Location\Models\Country;
use RainLab\Location\Models\Setting;
use Seeder;

class SetUpCountries extends Seeder
{
    public function run()
    {
        Country::query()->update(['is_enabled' => 1, 'is_pinned' => 0]);
        Country::query()->code('RU', 'UA')->update(['is_pinned' => 1]);
        if ($default = Country::query()->code('RU')->first()) {
            Setting::set('default_country', $default->id);
        }
    }
}
