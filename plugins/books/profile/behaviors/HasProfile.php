<?php

namespace Books\Profile\Behaviors;

use RainLab\User\Models\User;
use Books\Profile\Models\Profile;
use October\Rain\Extension\ExtensionBase;

class HasProfile extends ExtensionBase
{
    public function __construct(protected User $user)
    {
        $this->user->addFillable(['current_profile_id']);
        $this->user->hasMany['profiles'] = [Profile::class, 'key' => 'user_id', 'otherKey' => 'id'];
        $this->user->hasOne['profile'] = [Profile::class, 'key' => 'id', 'otherKey' => 'current_profile_id'];
    }

    public function getProfilesAsOptionsAttribute()
    {
        return $this->user->profiles()->select(['id', 'username'])->get();
    }

    public function setUserName(?string $username = null)
    {
        $profile = $this->user->profile;
        $profile->username = $username ?? $this->user->username;

        return $profile->save();
    }


}
