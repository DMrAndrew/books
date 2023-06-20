<?php

namespace App\traits;

use October\Rain\Database\Builder;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

trait HasUserIPScopes
{
    use HasUserScope;
    use HasIPScope;

    public function scopeUserOrIpWithDefault(Builder $builder, ?User $user = null, ?string $ip = null)
    {
        $ip ??= request()->ip();
        $user ??= Auth::getUser();
        return $builder->userOrIp($user, $ip);
    }

    public function scopeUserOrIp(Builder $builder, ?User $user = null, ?string $ip = null)
    {
        return $builder->when($user, fn($q) => $q->user($user), fn($q) => $q->user(null)->ip($ip));
    }

}
