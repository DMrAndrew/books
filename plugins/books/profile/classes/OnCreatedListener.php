<?php

namespace Books\Profile\Classes;

use October\Rain\Database\Model;

class OnCreatedListener
{

    public function __construct(protected Model $model)
    {
    }

    public function __invoke()
    {
        $profiler = $this->model->profiler();
        $profiler->update(['ids' => array_merge($profiler->getIds(), [$this->model->id])]);
        $profiler->save();
    }
}
