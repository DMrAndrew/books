<?php

namespace Books\Profile\Behaviors;

use Books\Profile\Classes\ProfileManager;
use Books\Profile\Models\Profile;
use Books\Profile\Models\Profiler;
use October\Rain\Extension\ExtensionBase;
use RainLab\User\Models\User;

class HasProfile extends ExtensionBase
{
    public function __construct(protected User $model)
    {
        $this->model->addFillable(['current_profile_id']);
        $this->model->hasMany['profilers'] = [Profiler::class, 'key' => 'profile_id', 'otherKey' => 'current_profile_id'];
        $this->model->hasMany['profiles'] = [Profile::class];
        $this->model->hasOne['currentProfile'] = [Profile::class, 'key' => 'id', 'otherKey' => 'current_profile_id'];
        $this->model->append(['profiles_list']);
        User::created(fn(User $user) => (new ProfileManager())->createProfile($user));
    }

    public function getProfilesListAttribute()
    {
        return $this->model->profiles()->select(['id', 'username'])->get();
    }

    public function setUserName(?string $username = null)
    {
        return $this->model->currentProfile()->update(['username' => $username ?? $this->model->username]);
    }
}
