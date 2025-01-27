<?php

namespace Books\Book\Classes\Enums;

enum ChapterSalesType: string
{
    case FREE = 'free';
    case PAY = 'pay';

    public function getLabel()
    {
        return match ($this) {
            self::FREE => 'Бесплатная глава',
            self::PAY => 'Платная глава',
        };
    }
}
