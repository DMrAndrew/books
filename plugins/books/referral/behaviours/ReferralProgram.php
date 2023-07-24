<?php

namespace Books\Referral\Behaviours;

use Books\Referral\Models\Referrals;
use Books\Referral\Models\Referrer;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\HasOne;
use October\Rain\Extension\ExtensionBase;
use RainLab\User\Models\User;

class ReferralProgram extends ExtensionBase
{
    public function __construct(protected User $user)
    {
        $this->user->hasMany['referrers'] = [Referrer::class, 'key' => 'user_id'];
        $this->user->hasOne['referrer'] = [
            Referrer::class,
            'key' => 'user_id',
            'scope' => [self::class, 'lastLink'],
        ];

        $this->user->hasMany['referrals'] = [Referrals::class, 'key' => 'user_id'];
    }

    /**
     * @param $query
     *
     * @return mixed
     */
    public static function lastLink($query)
    {
        return $query->orderByDesc('updated_at')->first();
    }
}
