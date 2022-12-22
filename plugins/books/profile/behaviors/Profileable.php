<?php

namespace Books\Profile\Behaviors;

use RainLab\User\Models\User;
use October\Rain\Database\Model;
use Books\Profile\Models\Profiler;
use Books\Profile\Classes\ProfiledScope;
use October\Rain\Extension\ExtensionBase;
use Books\Profile\Classes\ProfileEventHandler;

class Profileable extends ExtensionBase
{
    public function __construct(protected Model $model)
    {

        $this->model->belongsTo['user'] ??= [User::class, 'key' => 'user_id', 'otherKey' => 'id'];

        get_class($this->model)::addGlobalScope(new ProfiledScope());

    }

    public function attachToProfile()
    {
        (new ProfileEventHandler())->createdProfilableModel($this->model);
    }

    public function detachFromProfile()
    {
        (new ProfileEventHandler())->deletedProfilableModel($this->model);
    }

    public function profiler(): Profiler
    {
        return static::getProfiler($this->model);
    }

    public static function getProfiler(?Model $model = null, ?User $user = null): Profiler
    {
        $builder = ($user ?? $model->user)->profilers()->where('entity_type', '=', get_class($model ?? get_called_class()));
        if (!$builder->exists()) {
            $builder->create(['entity_type' => get_class($model)]);
        }

        return $builder->first();
    }
}
