<?php

namespace Books\Notifications\Classes\Events;

use Books\Collections\classes\CollectionEnum;
use Books\Collections\Models\Lib;
use Books\Notifications\Classes\NotificationTypeEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class BookSelling extends BaseEvent
{
    public string $eventName = 'Продажа';

    public string $eventDescription = 'На книгу в статусе "В работе" начат старт продаж в статусе книги "Завершено".';

    /**
     * @return array
     */
    public static function defaultParams(): array
    {
        return [
            'type' => NotificationTypeEnum::BOOKS->value,
            'icon' => 'book-stroked-24',
            'template' => 'book_selling',
        ];
    }

    /**
     * @param  array  $args
     * @param $eventName
     * @return array
     */
    public static function makeParamsFromEvent(array $args, $eventName = null): array
    {
        return array_merge(
            static::defaultParams(),
            [
                'book' => Arr::get($args, 0),
                'recipients' => static::getRecipients($args),
            ],
        );
    }

    /**
     * @param  array  $args
     * @return Collection|null
     */
    public static function getRecipients(array $args): ?Collection
    {
        $book = Arr::get($args, 0);

        return Lib::query()
            ->book($book)
            ->whereIn('type', [
                CollectionEnum::INTERESTED->value,
                CollectionEnum::READING->value,
            ])
            ->with('favorites.user')
            ->get()
            ->transform(static function (Lib $lib) {
                return $lib?->favorites->first()?->user;
            });
    }
}
