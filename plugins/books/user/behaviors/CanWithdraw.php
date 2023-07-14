<?php

namespace Books\User\Behaviors;

use Books\Wallet\Models\Transaction;
use Books\Withdrawal\Models\Withdrawal;
use Books\Withdrawal\Models\WithdrawalData;
use RainLab\User\Models\User;
use October\Rain\Extension\ExtensionBase;

class CanWithdraw extends ExtensionBase
{
    public function __construct(protected User $user)
    {
        $this->user->hasOne['withdrawalData'] = [WithdrawalData::class, 'key' => 'user_id'];
        $this->user->hasMany['withdrawals'] = [Withdrawal::class, 'key' => 'user_id'];
        $this->user->hasMany['transactions'] = [Transaction::class, 'key' => 'payable_id'];
    }
}
