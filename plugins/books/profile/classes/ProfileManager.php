<?php

namespace Books\Profile\Classes;

use Books\Profile\Models\Profile;
use Books\Profile\Models\ProfileSettings;
use Books\User\Classes\UserSettingsEnum;
use Books\User\Models\AccountSettings;
use Event;
use RainLab\User\Models\User;
use ValidationException;

class ProfileManager
{
    /**
     * @param  User  $user
     * @param  array  $payload
     * @param  bool  $activate
     * @return void
     *
     * @throws ValidationException
     */
    public function createProfile(User $user, array $payload = [], bool $activate = true): void
    {
        if ($user->profiles()->count() > 4) {
            throw new ValidationException('Превышен лимит профилей');
        }
        $user->profiles()->create([
            'username' => $payload['username'] ?? $user->username,
        ]);
        $profile = $user->profiles()->latest()->first();
        Event::fire('books.profile.created', [$profile]);
        if ($activate) {
            $this->switch($profile);
        }
    }

    /**
     * @param  User  $user
     * @param  bool  $reject
     * @return void
     */
    public function replaceUsernameFromClipboard(User $user, bool $reject = false): void
    {
        if ($username_clipboard = $user->profile->username_clipboard) {
            if (! $reject) {
                $user->profile->update(['username' => $username_clipboard, 'username_clipboard' => null, 'username_clipboard_comment' => null]);
                Event::fire('books.profile.username.modified', [$user]);
            } else {
                $user->profile->update(['username_clipboard' => null, 'username_clipboard_comment' => null]);
                Event::fire('books.profile.username.rejected', [$user]);
            }
        }
    }

    /**
     * @param  Profile|int  $profile
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

    public static function initUserSettings($user, bool $refresh = false): void
    {
        if ($refresh) {
            $user->profileSettings->each->delete();
            $user->profileSettings->each->save();
            $user->accountSettings->each->delete();
            $user->accountSettings->each->save();
        }
        $accountable = collect(UserSettingsEnum::accountable())->map(fn ($enum) => AccountSettings::fromEnum($enum));
        $user->accountSettings()->addMany($accountable);

        $profilable = collect(UserSettingsEnum::profilable())->map(fn ($enum) => ProfileSettings::fromEnum($enum));
        $user->profileSettings()->addMany($profilable);

        Event::fire('books.user.settings.'.$refresh ? 'init' : 'refreshed', [$user]);
    }
}
