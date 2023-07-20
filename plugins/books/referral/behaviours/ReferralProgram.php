<?php

namespace Books\Referral\Behaviours;

use Books\Referral\Models\Referrals;
use Books\Referral\Models\Referrer;
use October\Rain\Extension\ExtensionBase;
use RainLab\User\Models\User;

class ReferralProgram extends ExtensionBase
{
    public function __construct(protected User $user)
    {
        $this->user->hasOne['referrer'] = [Referrer::class, 'key' => 'user_id'];
        $this->user->hasMany['referrals'] = [Referrals::class, 'key' => 'user_id'];
    }
}
