<?php

namespace Books\User\Classes;

use Books\User\Models\TempAdultPass;
use Carbon\Carbon;
use RainLab\Location\Models\Country;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

class UserService
{
    public function __construct(protected User $user)
    {
    }

    public function update(array $payload)
    {
        $payload = collect($payload);

        $payload['show_birthday'] = !!($payload['show_birthday'] ?? false);
        $payload['see_adult'] = !!($payload['see_adult'] ?? false);

        if ($this->user->birthday) {
            $payload->forget('birthday');
        }

        if ($payload->has('see_adult')) {
            $payload['see_adult'] = $this->user->canSetAdult() ? $payload['see_adult'] : false;
        }

        if ($payload->has('country_id')) {
            if ($country = Country::query()->isEnabled()->find($payload->get('country_id'))) {
                $this->user->country_id = $country->id;
            }
        }
        $this->user->fill($payload->toArray());
        $this->user->save();
        return $this->user;
    }

    public static function allowedSeeAdult(): bool
    {
        if ($user = Auth::getUser()) {
            return $user->allowedSeeAdult();
        }

        return static::guestAllowedSeeAdult();
    }

    public static function guestAllowedSeeAdult()
    {
        return TempAdultPass::lookUp()->agree()->active()->exists();
    }

    public static function canBeAskedAdultPermission(): bool
    {
        if ($user = Auth::getUser()) {
            return $user->requiredAskAdult();
        }

        return static::canGuestBeAskedAdultPermission();
    }

    public static function canGuestBeAskedAdultPermission(): bool
    {
        return TempAdultPass::lookUp()->doesntExist();
    }
}
