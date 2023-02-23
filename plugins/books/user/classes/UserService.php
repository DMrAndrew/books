<?php

namespace Books\User\Classes;

use RainLab\Location\Models\Country;
use RainLab\User\Models\User;

class UserService
{
    public function __construct(protected User $user)
    {
    }

    public function update(array $payload)
    {
        $payload = collect($payload);

        $payload['show_birthday'] = !!$payload['show_birthday'];
        $payload['see_adult'] = !!$payload['see_adult'];

        if ($this->user->birthday) {
            $payload->forget('birthday');
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
}
