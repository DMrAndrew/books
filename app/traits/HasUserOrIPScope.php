<?php

namespace App\traits;

use Exception;
use October\Rain\Database\Builder;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

trait HasUserOrIPScope
{
    use HasUserScope;
    use HasIPScope;

    public function scopeUserOrIp(Builder $builder, ?User $user = null, ?string $ip = null)
    {
        $user ??= Auth::getUser();
        return $builder->when($user, fn($q) => $q->user($user), fn($q) => $q->ip($ip));
    }

    public function beforeCreate()
    {
        $this->fill([
            'user_id' => Auth::getUser()?->id,
            'ip' => request()->ip()
        ]);
    }
}
