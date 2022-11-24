<?php

namespace Books\Profile\Behaviors;

use RainLab\User\Models\User;
use Books\Profile\Models\Profile;
use Books\Profile\Models\Profiler;
use October\Rain\Extension\ExtensionBase;

class HasProfile extends ExtensionBase
{
    public function __construct(protected User $model)
    {
        $this->model->addFillable(['current_profile_id']);
        $this->model->hasMany['profilers'] = [Profiler::class, 'key' => 'profile_id', 'otherKey' => 'current_profile_id'];
        $this->model->hasMany['profiles'] = [Profile::class];
        $this->model->hasOne['currentProfile'] = [Profile::class, 'key' => 'id', 'otherKey' => 'current_profile_id'];
        $this->model->append(['profiles_list']);
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
