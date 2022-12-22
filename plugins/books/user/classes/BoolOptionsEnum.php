<?php

namespace Books\User\Classes;

enum BoolOptionsEnum: string
{
    case ON = 'on';
    case OFF = 'off';

    public function getLabel()
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
