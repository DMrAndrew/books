<?php

namespace Books\Reposts\behaviors;

use Books\Reposts\Models\Repost;
use October\Rain\Database\Model;
use October\Rain\Extension\ExtensionBase;
use RainLab\User\Models\User;

class Shareable extends ExtensionBase
{
    public function __construct(protected Model $model)
    {
        $this->model->morphMany['reposts'] = [Repost::class, 'name' => 'shareable'];
    }

    public function reposted(User $user): Repost
    {
        //->withoutSlaveScope()
        return $this->model->reposts()->firstOrCreate([
            'user_id' => $user->id
        ]);
    }
}
