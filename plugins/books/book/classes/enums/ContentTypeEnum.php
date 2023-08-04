<?php

namespace Books\Book\Classes\Enums;
enum ContentTypeEnum: int
{
    case DEFERRED_UPDATE = 1;
    case DEFERRED_DELETE = 2;
    case DEFERRED_CREATE = 3;

    public function label(): string
    {
        return match ($this) {
            self::DEFERRED_CREATE => 'Добавление контента',
            self::DEFERRED_UPDATE => 'Обновление контента',
            self::DEFERRED_DELETE => 'Удаление',
        };
    }
}
