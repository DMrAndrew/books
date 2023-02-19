<?php

namespace Books\Profile\Behaviors;

use October\Rain\Database\Model;
use Books\Profile\Models\Profiler;
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

}
