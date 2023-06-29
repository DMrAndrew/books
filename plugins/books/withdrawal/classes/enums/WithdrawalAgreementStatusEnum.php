<?php

namespace Books\Withdrawal\Classes\Enums;

enum WithdrawalAgreementStatusEnum: string
{
    case FILLING = 'filling';
    case SIGNING = 'signing';
    case VERIFYING = 'verifying';
    case CHECKING = 'checking';
    case RESTRICTED = 'restricted';
    case APPROVED = 'approved';

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::FILLING => 'Заполнение',
            self::SIGNING => 'Подписание',
            self::VERIFYING => 'Подтверждение',
            self::CHECKING => 'На проверке',
            self::RESTRICTED => 'Запрещен',
            self::APPROVED => 'Договор подписан',
        };
    }
}
