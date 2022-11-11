<?php

namespace Books\Profile\Behaviors;

use RainLab\User\Models\User;
use October\Rain\Database\Model;
use Books\Profile\Models\Profiler;
use Books\Profile\Classes\ProfiledScope;
use October\Rain\Extension\ExtensionBase;
use Books\Profile\Classes\OnDeleteListener;
use Books\Profile\Classes\OnCreatedListener;
//TODO refactor listeners
class Profileable extends ExtensionBase
{
    protected string $class;

    public function __construct(protected Model $model)
    {
        $this->class = get_class($this->model);

        $this->class::addGlobalScope(new ProfiledScope());

        $this->model->belongsTo['user'] ??= [User::class, 'key' => 'id', 'otherKey' => 'user_id'];

        $this->bindEvents();
    }

    /**
     * @return void
     */
    protected function bindEvents(): void
    {
        $this->class::created(fn($model) => (new OnCreatedListener($model))());
        $this->class::deleted(fn($model) => (new OnDeleteListener($model))());
    }

    public function attachToProfile()
    {
        (new OnCreatedListener($this->model))();
    }

    public function detachFromProfile()
    {
        (new OnDeleteListener($this->model))();
    }

    public function profiler(): Profiler
    {
        return static::getProfiler($this->model);
    }

    public static function getProfiler(Model $model, ?User $user = null): Profiler
    {
        $builder = ($user ?? $model->user)->profilers()->where('entity_type', '=', get_class($model));
        if (!$builder->exists()) {
            $builder->create(['entity_type' => get_class($model)]);
        }

        return $builder->first();
    }
}
