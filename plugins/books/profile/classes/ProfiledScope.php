<?php

namespace Books\Profile\Classes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use RainLab\User\Models\User;

class ProfiledScope implements Scope
{
    /**
     * @param  Builder  $builder
     * @param  Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        if ($user = $this->getQueryUser($builder)) {
            $ids = get_class($model)::getProfiler($model, $user)->getIds() ?? [];
            $builder->whereIn('id', $ids);
        }
    }

    public function extend(Builder $builder)
    {
        $builder->macro('allProfiles', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * @param  Builder  $builder
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
