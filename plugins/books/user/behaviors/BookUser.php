<?php

namespace Books\User\Behaviors;

use Books\User\Models\Country;
use Carbon\Carbon;
use October\Rain\Argon\Argon;
use RainLab\User\Models\User;
use October\Rain\Extension\ExtensionBase;

class BookUser extends ExtensionBase
{
    public function __construct(protected User $parent)
    {
        $this->parent->hasOne['country'] = [Country::class,'key' => 'id','otherKey' => 'country_id'];
        $this->parent->addValidationRule('birthday', 'nullable');
        $this->parent->addValidationRule('birthday', 'date');
        $this->parent->addValidationRule('show_birthday', 'nullable');
        $this->parent->addValidationRule('show_birthday', 'boolean');
        $this->parent->addValidationRule('country_id', 'nullable');
        $this->parent->addValidationRule('country_id', 'exists:books_user_countries,id');
        $this->parent->addFillable([
            'birthday',
            'show_birthday',
            'country_id',
            'required_post_register',
            'favorite_genres',
            'exclude_genres',
            'see_adult'
        ]);

        $this->parent->addDateAttribute('birthday');
        $this->parent->addJsonable([
            'favorite_genres',
            'exclude_genres'
        ]);
        //TODO перевод для birthday
    }

    public function scopeUsername($q, $name)
    {
        return $q->whereHas('profiles', function ($query) use ($name) {
            return $query->where('username', '=', $name);
        });
    }

    public function getBirthdayAttribute($value)
    {
        if ($value === '') return null;
        return $value;
    }

    public function setBirthdayAttribute($value)
    {
        $this->parent->attributes['birthday'] = ($value === '' || null) ? null :  Carbon::parse($value);
    }


}
