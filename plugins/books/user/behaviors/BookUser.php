<?php

namespace Books\User\Behaviors;

use Books\Profile\Models\ProfileSettings;
use Books\User\Classes\BookUserSettingsEnum;
use Books\User\Classes\PrivacySettingsEnum;
use Books\User\Classes\SettingsRelationCast;
use Books\User\Models\BookUserSettings;
use October\Rain\Database\Collection;
use RainLab\User\Models\User;
use Books\User\Models\Country;
use Books\Profile\Classes\ProfileManager;
use October\Rain\Extension\ExtensionBase;
use Books\User\Classes\ProfileAttributeCasts;
use Books\User\Classes\BirthdayAttributeCasts;

class BookUser extends ExtensionBase
{
    public function __construct(protected User $parent)
    {
        $this->parent->hasOne['country'] = [Country::class, 'key' => 'id', 'otherKey' => 'country_id'];
        $this->parent->hasMany['accountSettings'] = [BookUserSettings::class, 'key' => 'user_id', 'otherKey' => 'id'];
        $this->parent->hasMany['profileSettings'] = [ProfileSettings::class, 'key' => 'user_id', 'otherKey' => 'id'];
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
            'see_adult',
        ]);
        $this->parent->addDateAttribute('birthday');
        $this->parent->addCasts([
            'username' => ProfileAttributeCasts::class,
            'birthday' => BirthdayAttributeCasts::class
        ]);

        $this->parent->addJsonable([
            'favorite_genres',
            'exclude_genres'
        ]);
    }


    public function getNameAttribute()
    {
        return $this->parent->username;
    }

    public function scopeUsername($q, $name)
    {
        return $q->whereHas('profiles', function ($query) use ($name) {
            return $query->where('username', '=', $name);
        });
    }

    public function acceptClipboardUsername()
    {
        (new ProfileManager())->replaceUsernameFromClipboard($this->parent);
    }

    public function rejectClipboardUsername()
    {
        (new ProfileManager())->replaceUsernameFromClipboard($this->parent, true);
    }

    public function getCountryOptions(): array
    {
        return Country::lists('name', 'id');
    }


}
