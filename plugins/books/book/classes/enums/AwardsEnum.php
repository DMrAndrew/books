<?php

namespace Books\Book\Classes\Enums;

enum AwardsEnum: int
{
    case USUAL = 1;
    case SILVER = 2;
    case GOLD = 3;

    public function tag(): string
    {
        return match ($this) {
            self::USUAL => '',
            self::SILVER => 'silver',
            self::GOLD => 'gold',
        };
    }
}
