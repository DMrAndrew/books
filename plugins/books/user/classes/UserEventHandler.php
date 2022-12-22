<?php namespace Books\User\Classes;


use ValidationException;
use RainLab\User\Models\User;
use Books\User\Models\Country;
use Books\Profile\Classes\ProfileManager;
use Books\Catalog\Classes\FavoritesManager;

class UserEventHandler
{
    /**
     * @param User $user
     * @return void
     * @throws ValidationException
     */
    public function afterCreate(User $user): void
    {
        (new ProfileManager())->createProfile($user);
        (new FavoritesManager())->save($user);
        if (!$user->country()->exists()) {
            if ($country = Country::code('ru')->first()) {
                $user->update(['country_id' => $country->id], ['force' => true]);
            }
        }
        ProfileManager::initUserSettings($user);
    }
}
