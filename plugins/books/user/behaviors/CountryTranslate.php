<?php

namespace Books\User\Behaviors;

use October\Rain\Database\Builder;
use RainLab\Location\Models\Country;
use Monarobase\CountryList\CountryList;
use October\Rain\Extension\ExtensionBase;

class CountryTranslate extends ExtensionBase
{
    public function __construct(protected Country $country)
    {
    }

    public function getNameAttribute($value)
    {
        if (!$value || !$this->country->code) {
            return $value;
        }
        $service = (new CountryList());

        return $service->has($this->country->code)
            ? $service->getOne($this->country->code, 'ru')
            : $value;
    }

    public function scopeCode(Builder $builder, string ...$code)
    {
        return $builder->whereIn('code', $code);
    }
}
