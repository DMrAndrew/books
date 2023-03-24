<?php

namespace Books\Book\Classes\Enums;

enum WidgetEnum: string
{
    case cycle = 'cycle';
    case hotNew = 'hotNew';
    case readingWithThisOne = 'readingWithThisOne';
    case popular = 'popular';
    case new = 'new';
    case recommend = 'recommend';
    case interested = 'interested';
    case top = 'top';
    case todayDiscount = 'todayDiscount';
    case bestsellers = 'bestsellers';
    case gainingPopularity = 'gainingPopularity';
    case otherAuthorBook = 'otherAuthorBook';

    public function label(): string
    {
        return match ($this) {
            self::new => 'Новинки',
            self::hotNew => 'Горячие новинки',
            self::readingWithThisOne => 'С этой книгой читают',
            self::popular => 'Популярное',
            self::recommend => 'Рекомендуем',
            self::interested => 'Вы интересовались',
            self::top => 'Топы',
            self::todayDiscount => 'Скидки сегодня',
            self::bestsellers => 'Бестселлеры',
            self::gainingPopularity => 'Набирают популярность',
            self::otherAuthorBook => 'Другие книги автора',
            self::cycle => 'Циклы',
        };
    }
}
