<?php

namespace Books\Book\Classes\Enums;

enum BookStatus: string
{
    case WORKING = 'working';
    case COMPLETE = 'complete';
    case FROZEN = 'frozen';
    case HIDDEN = 'hidden';
    case PENDING = 'pending';
    case PARSING = 'parsing';
    case PARSING_FAILED = 'parsing_failed';

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
            self::PENDING, self::PARSING => 'Выполняется загрузка',
            self::PARSING_FAILED => 'Не удалось загрузить контент',
        };
    }

    public static function publicCases(): array
    {
        return [
            self::WORKING->value => self::WORKING,
            self::COMPLETE->value => self::COMPLETE,
            self::HIDDEN->value => self::HIDDEN,
        ];
    }
}
