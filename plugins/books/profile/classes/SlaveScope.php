<?php

namespace Books\Profile\Classes;

use Books\Profile\Models\Profile;
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
        $slaves = fn($i) => $i->profiler($model)->select('slave_id');
        if ($profile = $this->getQueryProfile($builder) ?? $this->getQueryUser($builder)?->profile) {
            $builder->whereIn($model->getQualifiedKeyName(), $slaves($profile))
                ->orWhereIn($model->getQualifiedKeyName(), $slaves($profile->user));
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
    private function getQueryProfile(Builder $builder): mixed
    {
        $profile_id = null;
        foreach ($builder->getQuery()->wheres as $where) {
            if ($where['type'] === 'Basic' && str_contains($where['column'] ?? '', 'profile_id')) {
                $profile_id = $where['value'];
            }
        }

        return Profile::find($profile_id);
    }

    /**
     * @param Builder $builder
     * @return mixed
     */
    private function getQueryUser(Builder $builder): mixed
    {
        $user_id = null;
        foreach ($builder->getQuery()->wheres as $where) {
            if ($where['type'] === 'Basic' && str_contains($where['column'] ?? '', 'user_id')) {
                $user_id = $where['value'];
            }
        }

        return User::find($user_id);
    }
}
