<?php

namespace Books\User\Classes;

use Books\Catalog\Classes\RecommendsService;
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
        (new RecommendsService())->save($user);
        CookieEnum::RECOMMEND->forget();
        if ($country = Country::getDefault()) {
            $user->service()->update([
                'country_id' => $country->id
            ]);
        }

    }
}
