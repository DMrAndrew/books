<?php

namespace Books\Shop\Classes;

enum OrderStatus: int
{
    case PROCESSED = 1;
    case SENT = 2;
    case RECEIVED = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::PROCESSED => 'Оформлен',
            self::SENT => 'Отправлен',
            self::RECEIVED => 'Получен',
        };
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
