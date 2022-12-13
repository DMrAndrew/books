<?php

namespace Books\User\Classes;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use October\Rain\Database\Collection;

class SettingsRelationCast implements CastsAttributes
{

    /**
     * @param $model
     * @param string $key
     * @param $value
     * @param array $attributes
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes)
    {
        return Collection::make(
            BookUserSettingsEnum::cases()
        );
    }

    /**
     * @param $model
     * @param string $key
     * @param $value
     * @param array $attributes
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes)
    {
        return  $value;
    }
}
