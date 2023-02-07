<?php

namespace Books\User\Behaviors;

use Books\Book\Models\Tag;
use Books\Book\Models\Book;
use Books\Book\Models\Cycle;
use Carbon\Carbon;
use RainLab\User\Models\User;
use Books\User\Models\Country;
use Books\User\Models\Settings;
use Books\User\Models\AccountSettings;
use October\Rain\Extension\ExtensionBase;
use Books\Profile\Models\ProfileSettings;
use Books\Profile\Classes\ProfileManager;
use Books\User\Classes\ProfileAttributeCasts;
use Books\User\Classes\BirthdayAttributeCasts;

class BookUser extends ExtensionBase
{
    public function __construct(protected User $parent)
    {
        $this->parent->belongsTo['country'] = [Country::class, 'key' => 'country_id', 'otherKey' => 'id'];
        $this->parent->hasMany['tags'] = [Tag::class, 'key' => 'user_id', 'otherKey' => 'id'];
        $this->parent->hasMany['cycles'] = [Cycle::class, 'key' => 'user_id', 'otherKey' => 'id'];
        $this->parent->hasMany['settings'] = [Settings::class, 'key' => 'user_id', 'otherKey' => 'id'];
        $this->parent->hasMany['accountSettings'] = [AccountSettings::class, 'key' => 'user_id', 'otherKey' => 'id'];
        $this->parent->hasMany['profileSettings'] = [ProfileSettings::class, 'key' => 'user_id', 'otherKey' => 'id'];
        $this->parent->hasMany['books'] = [Book::class, 'key' => 'user_id', 'otherKey' => 'id',];
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
            'loved_genres',
            'unloved_genres',
            'exclude_genres',
            'see_adult',
            'asked_adult_agreement'
        ]);
        $this->parent->addDateAttribute('birthday');
        $this->parent->addCasts([
            'username' => ProfileAttributeCasts::class,
            'birthday' => BirthdayAttributeCasts::class,
        ]);
        $this->parent->addJsonable([
            'favorite_genres',
            'exclude_genres',
            'loved_genres',
            'unloved_genres'
        ]);
    }

    public function setBirthdayAttribute($value)
    {
        $this->parent->attributes['birthday'] = Carbon::parse($value);
    }

    public function getNameAttribute()
    {
        return $this->parent->username;
    }

    public function scopeCoauthorsAutocomplite($q, $name)
    {
        return $q->whereHas('profiles', function ($query) use ($name) {
            return $query->where('username', 'like', "%$name%");
        });
    }

    public function scopeUsername($q, $name)
    {
        return $q->whereHas('profiles', function ($query) use ($name) {
            return $query->where('username', '=', $name);
        });
    }

    public function acceptClipboardUsername()
    {
        (new ProfileManager())->replaceUsernameFromClipboard(user: $this->parent);
    }

    public function rejectClipboardUsername()
    {
        (new ProfileManager())->replaceUsernameFromClipboard(user: $this->parent, reject: true);
    }

    public function getCountryOptions(): array
    {
        return Country::lists('name', 'id');
    }

    public function allowedSeeAdult(): bool
    {
        return !$this->parent->birthday || !$this->parent->asked_adult_agreement || !$this->parent->see_adult;
    }


}
