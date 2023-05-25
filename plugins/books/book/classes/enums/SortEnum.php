<?php

namespace Books\Book\Classes\Enums;

enum SortEnum: string
{
    case default = 'default';
    case popular_day = 'popular_day';
    case popular_week = 'popular_week';
    case popular_month = 'popular_month';
    case new = 'new';
    case hotNew = 'hotNew';
    case gainingPopularity = 'gainingPopularity';
    case topRate = 'topRate';
    case discount = 'discount';

    public function label(): string
    {
        return match ($this) {
            self::popular_day => 'Популярные за сегодня',
            self::popular_week => 'Популярные за неделю',
            self::popular_month => 'Популярные за месяц',
            self::new => 'Новинки',
            self::hotNew => 'Горячие новинки',
            self::gainingPopularity => 'Набирают популярность',
            self::topRate => 'С высоким рейтингом',
            self::discount => 'По размеру скидки',
            self::default => 'По умолчанию',
        };
    }

    public static function default(): SortEnum
    {
        return self::default;
    }
}
