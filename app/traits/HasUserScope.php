<?php

namespace App\traits;

use October\Rain\Database\Builder;
use RainLab\User\Models\User;

trait HasUserScope
{
    public function scopeUser(Builder $builder, ?User $user = null): Builder
    {
        return $builder->where($this->qualifyColumn('user_id'), '=', $user?->id);
    }
}
