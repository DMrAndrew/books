<?php

namespace Books\Profile\Classes;

use Books\Profile\Models\Profiler;
use October\Rain\Database\Model;

class ProfilerService
{
    public function __construct(protected Model $model)
    {

    }

    public function add(): void
    {
        $master = $this->model->isAccountable() ? $this->model->user : $this->model->user->profile;
        $profiler = new Profiler();
        $profiler->master()->associate($master);
        $profiler->slave()->associate($this->model);
        $profiler->save();

    }

    public function remove(): void
    {
        $this->model->profiler()?->delete();
    }

}
