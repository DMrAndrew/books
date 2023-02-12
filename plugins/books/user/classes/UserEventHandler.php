<?php

namespace Books\User\Classes;

use Books\Catalog\Classes\FavoritesManager;
use Books\Profile\Classes\ProfileManager;
use Books\User\Models\Country;
use RainLab\User\Models\User;
use ValidationException;

class UserEventHandler
{
    /**
     * @param  User  $user
     * @return void
     *
     * @throws ValidationException
     */
    public function afterCreate(User $user): void
    {
        (new ProfileManager())->createProfile($user);
        (new FavoritesManager())->save($user);
        if (! $user->country()->exists()) {
            if ($country = Country::code('ru')->first()) {
                $user->update(['country_id' => $country->id], ['force' => true]);
            }
        }
        ProfileManager::initUserSettings($user);
    }
}
