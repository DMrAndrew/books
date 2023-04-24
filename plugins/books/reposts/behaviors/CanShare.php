<?php

namespace Books\Reposts\behaviors;

use Books\Reposts\Models\Repost;
use October\Rain\Database\Model;
use October\Rain\Extension\ExtensionBase;
use RainLab\User\Models\User;

class CanShare extends ExtensionBase
{
    public function __construct(protected User $user)
    {
        $this->user->hasMany['reposts'] = [Repost::class, 'key' => 'user_id', 'otherKey' => 'id'];
    }

    protected function reposted(Model $model): Repost
    {
        return $model->reposted($this->user);
    }


}
