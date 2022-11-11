<?php

namespace Books\Profile\Classes;

use ValidationException;
use RainLab\User\Models\User;
use Books\Profile\Models\Profile;

class ProfileManager
{
    public function switch(Profile|int $profile): bool
    {
        $profile = is_int($profile) ? Profile::find($profile) : $profile;
        return $profile->user->update(['current_profile_id' => $profile->id]);
    }

    public function createProfile(User $user, $payload = [], bool $activate = true): void
    {
        if ($user->profiles()->count() > 4) {
            throw new ValidationException('Превышен лимит профилей');
        }
        $user->profiles()->create([
            'username' => $payload['username'] ?? $user->username
        ]);

        if ($activate) {
            $user->update(['current_profile_id' => $user->profiles()->latest()->first()->id]);
        }
    }
}
