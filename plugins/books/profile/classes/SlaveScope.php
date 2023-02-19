<?php

namespace Books\Profile\Classes;

use Books\Book\Models\Chapter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use RainLab\User\Models\User;

class SlaveScope implements Scope
{
    /**
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {

        if ($user = $this->getQueryUser($builder)) {
            $builder->whereIn('id', $user->profiler($model)->select('slave_id'))
                ->orWhereIn('id', $user->profile->profiler($model)->select('slave_id'));
        }
    }

    public function extend(Builder $builder)
    {
        $builder->macro('withoutSlaveScope', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * @param Builder $builder
     * @return mixed
     */
    private function getQueryUser(Builder $builder): mixed
    {
        $user_id = null;
        foreach ($builder->getQuery()->wheres as $where) {
            if ($where['type'] === 'Basic' && str_contains($where['column'], 'user_id')) {
                $user_id = $where['value'];
            }
        }

        return User::find($user_id);
    }
}
