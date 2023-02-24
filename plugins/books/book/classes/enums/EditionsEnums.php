<?php

namespace Books\Book\Classes\Enums;

enum EditionsEnums: int
{
    case Ebook = 1;

    case Audio = 2;
    case Physic = 3;
    case Comics = 4;

    public static function default(): EditionsEnums
    {
        return self::Ebook;
    }

    public function label(): string
    {
        return match ($this) {
            self::Ebook => 'Электронная книга',
            self::Audio => 'Аудиокниги',
            self::Physic => 'Бумажная книга',
            self::Comics => 'Комикс',
        };
    }

    public function labelShort(): string
    {
        return match ($this) {
            self::Ebook => 'Электронная',
            self::Audio => 'Аудио',
            self::Physic => 'Бумажная',
            self::Comics => 'Комикс',
        };
    }

    public function labelPlural(): string
    {
        return match ($this) {
            self::Ebook => 'Электронные книги',
            self::Audio => 'Аудиокниги',
            self::Physic => 'Бумажные книги',
            self::Comics => 'Комиксы',
        };
    }

    public static function toArray(): array
    {
        return [
            self::Ebook->value => self::Ebook,
            self::Audio->value => self::Audio,
            self::Physic->value => self::Physic,
            self::Comics->value => self::Comics,
        ];
    }
}
