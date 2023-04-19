<?php

namespace Books\Profile\Factories;

use Books\Profile\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition()
    {
        return [
            'username' => fake('ru_RU')->userName(),
        ];
    }
}
