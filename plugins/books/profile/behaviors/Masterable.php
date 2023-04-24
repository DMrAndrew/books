<?php

namespace Books\Profile\Behaviors;

use October\Rain\Database\Model;
use Books\Profile\Models\Profiler;
use October\Rain\Database\Relations\BelongsToMany;
use October\Rain\Extension\ExtensionBase;

class Masterable extends ExtensionBase
{
    public function __construct(protected Model $model)
    {
        $this->model->morphMany['profilers'] = [Profiler::class, 'name' => 'master'];
    }

    public function profiler(Model $model)
    {
        return $this->model->profilers()->slaveType($model);
    }

    public function belongsToManyTroughProfiler(string $class): BelongsToMany
    {
        return $this->model->belongsToMany($class, (new Profiler())->getTable(), 'master_id', 'slave_id')
            ->where('master_type', get_class($this->model))->where('slave_type', $class);
    }

}
