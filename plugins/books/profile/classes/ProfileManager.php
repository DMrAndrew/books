<?php

namespace Books\Profile\Classes;

use Event;
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
        $profile = $user->profiles()->latest()->first();
        Event::fire('books.profile.created', [$profile]);
        if ($activate) {
            $this->switch($profile);
        }
    }

    /**
     * @param User $user
     * @param bool $reject
     * @return void
     */
    public function replaceUsernameFromClipboard(User $user, bool $reject = false): void
    {
        if ($username_clipboard = $user->profile->username_clipboard) {
            if (!$reject) {
                $user->profile->update(['username' => $username_clipboard, 'username_clipboard' => null]);
                Event::fire('books.profile.username.modified', [$user]);
            } else {
                $user->profile->update(['username_clipboard' => null]);
                Event::fire('books.profile.username.rejected', [$user]);
            }
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
        $user->save();
        Event::fire('books.profile.switched', [$profile]);
        return true;
    }
}
