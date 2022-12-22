<?php

namespace Books\User\Classes;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class ProfileAttributeCasts implements CastsAttributes
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
        return $model->profile?->{$key} ?? $attributes[$key];
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
        return $value;
    }
}
