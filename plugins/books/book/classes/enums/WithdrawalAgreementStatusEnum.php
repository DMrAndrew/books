<?php

namespace Books\Book\Classes\Enums;

enum WithdrawalAgreementStatusEnum: string
{
    case SIGNING = 'signing';
    case CHECKING = 'checking';
    case RESTRICTED = 'restricted';
    case APPROVED = 'approved';

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::SIGNING => 'Подписание',
            self::CHECKING => 'На проверке',
            self::RESTRICTED => 'Запрещен',
            self::APPROVED => 'Договор подписан',
        };
    }
}
