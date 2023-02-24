<?php

namespace Books\User\Classes;

use Books\Catalog\Classes\FavoritesManager;
use Books\Profile\Classes\ProfileService;
use Books\Profile\Models\Profile;
use RainLab\Location\Models\Country;
use RainLab\User\Models\User;
use ValidationException;

class UserEventHandler
{
    /**
     * @param User $user
     * @return void
     *
     * @throws ValidationException
     */
    public function afterCreate(User $user): void
    {
        (new ProfileService(new Profile()))->createProfile(user: $user);
        (new FavoritesManager())->save($user);
        if ($country = Country::where('code', 'RU')->first()) {
            $user->service()->update([
                'country_id' => $country->id
            ]);
        }

    }
}
