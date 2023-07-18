<?php

namespace Books\User\Behaviors;

use Books\Wallet\Models\Transaction;
use Books\Withdrawal\Classes\Enums\WithdrawalAgreementStatusEnum;
use Books\Withdrawal\Classes\Enums\WithdrawalStatusEnum;
use Books\Withdrawal\Models\Withdrawal;
use Books\Withdrawal\Models\WithdrawalData;
use October\Rain\Database\Builder;
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

    /**
     * @param Builder $builder
     * @param array $data
     *
     * @return Builder
     */
    public function scopeWithdrawalAgreementStateFilter(Builder $builder, array $data): Builder
    {
        /**
         * Договор подписан
         */
        if (key_exists('approved', $data)) {
            $builder->whereHas('withdrawalData', function ($query) {
                $query->where('agreement_status', $agreementStatus = WithdrawalAgreementStatusEnum::APPROVED);
            });
        }

        /**
         * Вывод разрешен
         */
        if (key_exists('withdraw_allowed', $data)) {
            $builder->whereHas('withdrawalData', function ($query) {
                $query->where('withdrawal_status', $withdrawAllowed = WithdrawalStatusEnum::ALLOWED);
            });
        }

        /**
         * Вывод заморожен пользователем
         */
        if (key_exists('withdraw_not_frozen', $data)) {
            $builder->whereHas('withdrawalData', function ($query) {
                $query->where('withdraw_frozen', false);
            });
        }

        return $builder;
    }
}
