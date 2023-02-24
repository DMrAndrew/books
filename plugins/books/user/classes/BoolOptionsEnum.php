<?php

namespace Books\User\Classes;

enum BoolOptionsEnum: string
{
    case ON = 'on';
    case OFF = 'off';

    public function label(): string
    {
        return match ($this) {
            self::ON => 'Да',
            self::OFF => 'Нет',
        };
    }

    public static function default(): string
    {
        return self::OFF->value;
    }
}
