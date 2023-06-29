<?php

namespace Books\Book\Classes\Enums;

enum SellStatisticSellTypeEnum: string
{
    case SELL = 'sell';
    case SUBSCRIBE = 'subscribe';

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::SELL => 'Продажа',
            self::SUBSCRIBE => 'Подписка',
        };
    }
}
