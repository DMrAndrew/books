<?php

namespace Books\Notifications\Classes;

enum NotificationTypeEnum: string
{
    case BOOKS = 'books';
    case BLOG = 'blog';
    case VIDEOBLOG = 'videoblog';
    case DISCOUNTS = 'discounts';
    case SYSTEM = 'system';

    public function label(): string
    {
        return match ($this) {
            self::BOOKS => 'Книги',
            self::BLOG => 'Блог',
            self::VIDEOBLOG => 'Видеоблог',
            self::DISCOUNTS => 'Скидки',
            self::SYSTEM => 'Администрация',
        };
    }
}
