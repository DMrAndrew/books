<?php

namespace Books\Catalog\Classes;

enum BookTypeEnum: string
{
    case EBook = 'ebook';

    case Audio = 'audio';
    case Physical = 'physical';

    case Comic = 'comic';

    public function getLabel(): string
    {
        return match ($this) {
            self::EBook => 'Электронная',
            self::Audio => 'Аудио',
            self::Physical => 'Бумажная',
            self::Comic => 'Комикс'
        };
    }

    public function getPluralLabel(): string
    {
        return match ($this) {
            self::EBook => 'Электронные',
            self::Audio => 'Аудио',
            self::Physical => 'Бумажные',
            self::Comic => 'Комиксы'
        };
    }
}
