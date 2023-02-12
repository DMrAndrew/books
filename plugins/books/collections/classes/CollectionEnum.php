<?php

namespace Books\Collections\classes;

enum CollectionEnum: int
{
    case WATCHED = 1;
    case INTERESTED = 2;
    case READING = 3;
    case READ = 4;
    case LOVED = 5;

    public static function default(): CollectionEnum
    {
        return self::WATCHED;
    }

    public function label(): string
    {
        return match ($this) {
            self::WATCHED => 'Вы интересовались',
            self::INTERESTED => 'Хочу прочесть',
            self::READING => 'Читаю сейчас',
            self::READ => 'Прочитано',
            self::LOVED => 'Любимые',
        };
    }
}
