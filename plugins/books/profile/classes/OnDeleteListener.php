<?php

namespace Books\Profile\Classes;

use October\Rain\Database\Model;

class OnDeleteListener
{
    public function __construct(protected Model $model)
    {
    }

    public function __invoke()
    {
        $profiler = $this->model->profiler();
        $profiler->ids = array_values(array_diff($profiler->getIds(), [$this->model->id]));
        $profiler->save();
    }
}
