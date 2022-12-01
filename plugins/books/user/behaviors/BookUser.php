<?php

namespace Books\User\Behaviors;

use RainLab\User\Models\User;
use October\Rain\Extension\ExtensionBase;

class BookUser extends ExtensionBase
{
    public function __construct(protected User $parent)
    {
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
            'exclude_genres'
        ]);
        $this->parent->addCasts([
            'favorite_genres' => 'array',
            'exclude_genres' => 'array',
        ]);

        //TODO перевод для birthday
    }

    public function scopeUsername($q, $name)
    {
        return $q->whereHas('profiles', function ($query) use ($name) {
            return $query->where('username', '=', $name);
        });
    }

}
