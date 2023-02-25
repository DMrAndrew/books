<?php

namespace Books\Profile\Classes;

use Books\Profile\Models\Profile;
use Books\User\Classes\UserSettingsEnum;
use Event;
use RainLab\User\Models\User;
use ValidationException;

class ProfileService
{

    public function __construct(protected Profile $profile)
    {
    }

    /**
     * @param User $user
     * @param array $payload
     * @param bool $activate
     * @return void
     *
     * @throws ValidationException
     */
    public function createProfile(User $user, array $payload = [], bool $activate = true): Profile
    {
        $this->profile->user = $user;
        $payload['username'] ??= $user->username;

        return $this->newProfile($payload);

    }

    public function newProfile(array $payload, bool $activate = true): Profile
    {
        $user = $this->profile->user;
        $validator = \Validator::make(
            $payload,
            (new Profile())->rules
        );
        if ($validator->fails()) {
            throw new  ValidationException($validator);
        }

        $this->profile = $user->profiles()->create($payload);
        Event::fire('books.profile.created', [$this->profile]);
        if ($activate) {
            $this->switch();
            $this->initUserSettings();
        }

        return $this->profile;
    }

    /**
     * @param bool $reject
     * @return void
     */
    public function replaceUsernameFromClipboard(bool $reject = false): void
    {
        if ($username_clipboard = $this->profile->username_clipboard) {
            if (!$reject) {
                $this->profile->update(['username' => $username_clipboard, 'username_clipboard' => null, 'username_clipboard_comment' => null]);
                Event::fire('books.profile.username.modified', [$this->profile]);
            } else {
                $this->profile->update(['username_clipboard' => null, 'username_clipboard_comment' => null]);
                Event::fire('books.profile.username.rejected', [$this->profile]);
            }
        }
    }

    /**
     * @return bool
     */
    public function switch(): bool
    {
        $this->profile->user->update(['current_profile_id' => $this->profile->id]);
        Event::fire('books.profile.switched', [$this->profile]);

        return true;
    }

    public function initUserSettings($refresh = false): void
    {
        if ($refresh) {
            $this->profile->user->settings->each->delete();
        }

        collect(UserSettingsEnum::cases())->map(function (UserSettingsEnum $setting) {
            $this->profile->user->settings()
                ->firstOrCreate(['type' => $setting->value, 'value' => $setting->defaultValue()]);
        });

        Event::fire('books.profile.settings.initialized', [$this->profile]);
    }
}
