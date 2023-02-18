<?php

namespace Books\Profile\Behaviors;

use Books\Profile\Classes\ProfilerService;
use Books\Profile\Classes\SlaveScope;
use October\Rain\Database\Model;
use October\Rain\Extension\ExtensionBase;
use RainLab\User\Models\User;

class Slavable extends ExtensionBase
{
    public function __construct(protected Model $model)
    {
        $this->model->belongsTo['user'] ??= [User::class, 'key' => 'user_id', 'otherKey' => 'id'];

        get_class($this->model)::addGlobalScope(new SlaveScope());
    }

    public function isAccountable(): bool
    {
        return false;
    }

    public function profilerService(): ProfilerService
    {
        return new ProfilerService($this->model);
    }

}
