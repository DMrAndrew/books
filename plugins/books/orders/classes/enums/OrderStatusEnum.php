<?php
declare(strict_types=1);

namespace Books\Orders\Classes\Enums;

enum OrderStatusEnum: int
{
    case CREATED = 1;
    case PENDING = 2;
    case PAID = 3;
    case CANCELED = 4;

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Электронная книга',
            self::PENDING => 'Ожидает оплаты',
            self::PAID => 'Оплачен',
            self::CANCELED => 'Отменен',
        };
    }

    public static function default(): OrderStatusEnum
    {
        return self::CREATED;
    }
}
