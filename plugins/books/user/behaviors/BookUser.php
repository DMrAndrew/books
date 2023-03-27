<?php

namespace Books\User\Behaviors;

use Books\Book\Models\Cycle;
use Books\Book\Models\Tag;
use Books\Comments\Models\Comment;
use Books\Profile\Models\Profile;
use Books\User\Classes\UserService;
use Books\User\Models\Settings;
use Carbon\Carbon;
use October\Rain\Extension\ExtensionBase;
use RainLab\User\Models\User;
use ValidationException;

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

    public function maxProfilesCount(): int
    {
        return Profile::MAX_USER_PROFILES_COUNT;
    }

    public function setBirthdayAttribute($value)
    {
        if ($value && ! $this->parent->birthday) {
            $this->parent->attributes['birthday'] = Carbon::parse($value);
            $this->parent->birthday->lessThan(Carbon::now()) ?: throw new ValidationException(['birthday' => 'Дата рождения не может быть больше текущего дня']);
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

    public function fetchRequired(): bool
    {
        return $this->requiredPostRegister() || $this->requiredAskAdult();
    }

    public function requiredPostRegister()
    {
        return $this->parent->required_post_register;
    }

    public function requiredAskAdult(): bool
    {
        return $this->parent->asked_adult_agreement == 0 && $this->parent->canSetAdult();
    }

    public function getNameAttribute()
    {
        return $this->parent->username;
    }

    public function scopeUsernameLike($q, $name)
    {
        return $q->whereHas('profiles', fn ($profile) => $profile->usernameLike($name));
    }

    public function scopeUsername($q, $name)
    {
        return $q->whereHas('profiles', fn ($profile) => $profile->username($name));
    }
}
