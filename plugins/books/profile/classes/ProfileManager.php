<?php

namespace Books\Profile\Classes;

use ValidationException;
use RainLab\User\Models\User;
use Books\Profile\Models\Profile;

class ProfileManager
{
    /**
     * @param User $user
     * @param array $payload
     * @param bool $activate
     * @return void
     * @throws ValidationException
     */
    public function createProfile(User $user, array $payload = [], bool $activate = true): void
    {
        if ($user->profiles()->count() > 4) {
            throw new ValidationException('Превышен лимит профилей');
        }
        $user->profiles()->create([
            'username' => $payload['username'] ?? $user->username
        ]);

        if ($activate) {
            $this->switch($user->profiles()->latest()->first());
        }
    }

    /**
     * @param Profile|int $profile
     * @return bool
     */
    public function switch(Profile|int $profile): bool
    {
        $profile = is_int($profile) ? Profile::find($profile) : $profile;
        $user = $profile->user;
        $user->current_profile_id = $profile->id;
        return $user->save();
    }
}
