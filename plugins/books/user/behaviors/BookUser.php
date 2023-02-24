<?php

namespace Books\User\Behaviors;

use Books\Comments\Models\Comment;
use Books\User\Classes\UserService;
use Carbon\Carbon;
use Books\Book\Models\Tag;
use Books\Book\Models\Cycle;
use RainLab\User\Models\User;
use Books\User\Models\Settings;
use October\Rain\Extension\ExtensionBase;
use Books\Profile\Classes\ProfileService;

class BookUser extends ExtensionBase
{
    public function __construct(protected User $parent)
    {
        $this->parent->hasMany['comments'] = [Comment::class, 'key' => 'user_id', 'otherKey' => 'id'];
        $this->parent->hasMany['tags'] = [Tag::class, 'key' => 'user_id', 'otherKey' => 'id'];
        $this->parent->hasMany['cycles'] = [Cycle::class, 'key' => 'user_id', 'otherKey' => 'id'];
        $this->parent->hasMany['settings'] = [Settings::class, 'key' => 'user_id', 'otherKey' => 'id'];
        $this->parent->addValidationRule('birthday', 'nullable');
        $this->parent->addValidationRule('birthday', 'date');
        $this->parent->addValidationRule('show_birthday', 'boolean');
        $this->parent->addFillable([
            'birthday',
            'show_birthday',
            'country_id',
            'required_post_register',
            'favorite_genres',
            'loved_genres',
            'unloved_genres',
            'exclude_genres',
            'see_adult',
            'asked_adult_agreement',
        ]);
        $this->parent->addCasts([
            'show_birthday' => 'boolean',
            'see_adult' => 'boolean',
            'asked_adult_agreement' => 'boolean',
            'required_post_register' => 'boolean',
        ]);
        $this->parent->addDateAttribute('birthday');
        $this->parent->addJsonable([
            'favorite_genres',
            'exclude_genres',
            'loved_genres',
            'unloved_genres',
        ]);
    }

    public function service(): UserService
    {
        return new UserService($this->parent);
    }

    public function setBirthdayAttribute($value)
    {
        if ($value && !$this->parent->birthday) {
            $this->parent->attributes['birthday'] = Carbon::parse($value);
        }
    }

    public function canSetAdult(): bool
    {
        return $this->parent->birthday && abs(Carbon::now()->diffInYears($this->parent->birthday)) > 17;
    }

    public function allowedSeeAdult(): bool
    {
        return $this->parent->birthday && $this->parent->see_adult;
    }

    public function getNameAttribute()
    {
        return $this->parent->username;
    }

    public function scopeUsernameLike($q, $name)
    {
        return $q->whereHas('profiles', fn($profile) => $profile->usernameLike($name));
    }

    public function scopeUsername($q, $name)
    {
        return $q->whereHas('profiles', fn($profile) => $profile->username($name));
    }
}
