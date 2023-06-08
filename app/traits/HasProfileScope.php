<?php

namespace App\traits;

use October\Rain\Database\Builder;
use RainLab\User\Models\User;

trait HasProfileScope
{
    public function scopeProfile(Builder $builder, Profile $profile): Builder
    {
        return $builder->where('profile_id', '=', $profile->id);
    }
}
