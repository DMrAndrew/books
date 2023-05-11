<?php
declare(strict_types=1);

namespace Books\Payment\Classes\Enums;

enum PaymentStatusEnum: int
{
    case CREATED = 1;
    case PENDING = 2;
    case PAID = 3;
    case CANCELED = 4;

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Создана',
            self::PENDING => 'Ожидает оплаты',
            self::PAID => 'Оплачена',
            self::CANCELED => 'Отменена',
        };
    }

    public static function default(): PaymentStatusEnum
    {
        return self::CREATED;
    }
}
