<?php

namespace Books\Book\Classes\Enums;

enum BookStatus: string
{
    case WORKING = 'working';
    case COMPLETE = 'complete';
    case FROZEN = 'frozen';
    case HIDDEN = 'hidden';

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::WORKING => 'В работе',
            self::COMPLETE => 'Завершена',
            self::FROZEN => 'Заморожена',
            self::HIDDEN => 'Скрыта',
        };
    }

    public static function publicCases(): array
    {
        return [
            self::WORKING,
            self::COMPLETE,
            self::HIDDEN,
        ];
    }
}
