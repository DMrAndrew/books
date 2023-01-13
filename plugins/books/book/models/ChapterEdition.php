<?php

namespace Books\Book\Models;

enum ChapterEdition: string
{
    case FREE = 'free';
    case PAY = 'pay';

    public function getLabel()
    {
        return match ($this) {
            self::FREE => 'Бесплатная часть',
            self::PAY => 'Платная часть',
        };
    }
}
