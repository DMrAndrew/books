<?php

namespace Books\Profile\Classes;

use October\Rain\Database\Model;

class ProfilerService
{
    public function __construct(protected Model $model)
    {

    }

    public function add(): void
    {
        $this->update();
    }

    public function remove(): void
    {
        $this->update(action: 'remove');
    }


    public function update($action = 'add'): void
    {
        $master = $this->model->isAccountable() ? $this->model->user : $this->model->user->profile;
        $profiler = $master->profiler($this->model);
        $params = [$profiler->getIds() ?? [], [$this->model->id]];
        $profiler->update([$profiler->getIdsColumn() => $action === 'add' ? array_merge(...$params) : array_values(array_diff(...$params))]);
    }
}
