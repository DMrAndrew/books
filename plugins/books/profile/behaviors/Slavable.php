<?php

namespace Books\Profile\Behaviors;

use Books\Profile\Classes\ProfilerService;
use Books\Profile\Classes\SlaveScope;
use Books\Profile\Models\Profile;
use Books\Profile\Models\Profiler;
use October\Rain\Database\Model;
use October\Rain\Database\Relations\HasOneThrough;
use October\Rain\Extension\ExtensionBase;
use RainLab\User\Models\User;

class Slavable extends ExtensionBase
{
    public function __construct(protected Model $model)
    {
        $this->model->belongsTo['user'] ??= [User::class, 'key' => 'user_id', 'otherKey' => 'id'];
        $this->model->morphOne['profiler'] = [Profiler::class, 'name' => 'slave'];

        get_class($this->model)::addGlobalScope(new SlaveScope());
    }

    public function profile(): HasOneThrough
    {
        return $this->model->hasOneThrough(Profile::class, Profiler::class, 'slave_id','id','id','master_id')
            ->where('master_type', '=', Profile::class)
            ->where('slave_type', '=', get_class($this->model));
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
