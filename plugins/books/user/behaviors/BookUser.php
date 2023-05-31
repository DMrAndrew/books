<?php

namespace Books\User\Behaviors;

use Books\Book\Models\Edition;
use Books\Book\Models\UserBook;
use Books\Comments\Models\Comment;
use Books\Profile\Models\OperationHistory;
use Books\Profile\Models\Profile;
use Books\User\Classes\UserService;
use Books\User\Models\Settings;
use Carbon\Carbon;
use October\Rain\Extension\ExtensionBase;
use RainLab\User\Models\User;
use ValidationException;

class BookUser extends ExtensionBase
{
    const MIN_BIRTHDAY = '01.01.1940';

    public function __construct(protected User $parent)
    {
        $this->parent->hasMany['comments'] = [Comment::class, 'key' => 'user_id', 'otherKey' => 'id'];
        $this->parent->hasMany['settings'] = [Settings::class, 'key' => 'user_id', 'otherKey' => 'id'];
        $this->parent->hasMany['operations'] = [
            OperationHistory::class,
            'key' => 'user_id',
            'otherKey' => 'id',
            'order' => 'id desc',
        ];
        $this->parent->addValidationRule('birthday', 'nullable');
        $this->parent->addValidationRule('birthday', 'date');
        $this->parent->addValidationRule('show_birthday', 'boolean');
        $this->parent->addValidationRule('username', 'required');
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
        if ($value) {
            if (! $this->parent->birthday) {
                $date = Carbon::parse($value);
                $date->lessThan(today()) ?: throw new ValidationException(['birthday' => 'Дата рождения не может быть больше текущего дня']);
                $date->gte(Carbon::parse(self::MIN_BIRTHDAY)) ?: throw new ValidationException(['birthday' => 'Дата рождения не может быть меньше '.self::MIN_BIRTHDAY]);
                $this->parent->attributes['birthday'] = $date;
            }
        } else {
            $this->parent->attributes['birthday'] = null;
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

    public function bookIsBought(Edition $edition): bool
    {
        $user = $this->parent;

        $userHasBook = UserBook
            ::whereHasMorph('ownable', [Edition::class], function($q) use ($edition){
                $q->where('id', $edition->id);
            })
            ->whereHas('user', function ($query) use ($user) {
                $query->where('id', $user->id);
            })
            ->first();

        return (bool) $userHasBook?->exists;
    }
}
