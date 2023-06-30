<?php

namespace Books\Book\Classes\Enums;

use Books\Book\Classes\WidgetService;
use Exception;

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

    public static function listable(): array
    {
        return [
            self::hotNew->value => self::hotNew,
            self::gainingPopularity->value => self::gainingPopularity,
            self::bestsellers->value => self::bestsellers,
            self::top->value => self::top,
            self::recommend->value => self::recommend,
            self::todayDiscount->value => self::todayDiscount,
            self::new->value => self::new
        ];
    }

    public function isListable(): bool
    {
        return in_array($this, self::listable());
    }

    public function mapSortEnum(): SortEnum
    {
        return match ($this) {
            default => SortEnum::default,
            self::new => SortEnum::new,
            self::todayDiscount => SortEnum::discount,
            self::gainingPopularity => SortEnum::gainingPopularity,

        };
    }

    /**
     * Minutes of cache
     * @return int
     */
    public function defaultCacheTTL(): int
    {
        return match ($this) {
            default => 10,
            self::interested, self::cycle => 3,
        };
    }

    /**
     * @throws Exception
     */
    public function service(...$args): WidgetService
    {
        return new WidgetService($this, ...$args);
    }
}
