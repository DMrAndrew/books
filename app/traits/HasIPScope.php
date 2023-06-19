<?php

namespace App\traits;

use October\Rain\Database\Builder;

trait HasIPScope
{
    public function scopeIp(Builder $builder, ?string $ip = null): Builder
    {
        $ip ??= request()->ip();
        return $builder->where('ip', $ip);
    }

}
