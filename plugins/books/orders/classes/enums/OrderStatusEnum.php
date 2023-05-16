<?php
declare(strict_types=1);

namespace Books\Orders\Classes\Enums;

enum OrderStatusEnum: int
{
    case CREATED = 1;
    case PENDING = 2;
    case AWAIT_APPROVE = 3;
    case PAID = 4;
    case CANCELED = 5;

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Создан',
            self::PENDING => 'Ожидает оплаты',
            self::AWAIT_APPROVE => 'Ожидает подтверждения',
            self::PAID => 'Оплачен',
            self::CANCELED => 'Отменен',
        };
    }

    public static function default(): OrderStatusEnum
    {
        return self::CREATED;
    }
}
