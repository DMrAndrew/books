<?php

namespace Books\Withdrawal\Classes\Enums;

enum WithdrawalStatusEnum: string
{
    case ALLOWED = 'allowed';
    case RESTRICTED = 'prohibited';

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::ALLOWED => 'Вывод разрешен',
            self::RESTRICTED => 'Вывод запрещен',
        };
    }
}
