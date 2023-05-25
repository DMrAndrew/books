<?php

namespace Books\Notifications\Classes\Events;

use Books\Collections\classes\CollectionEnum;
use Books\Collections\Models\Lib;
use Books\Notifications\Classes\NotificationTypeEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class BookCompleted extends BaseEvent
{
    public string $eventName = 'Завершение книги';

    public string $eventDescription = 'Автор завершил написание книги, которая была "В работе" и указал статус "Завершена" в создании книги.';

    /**
     * @return array
     */
    public static function defaultParams(): array
    {
        return [
            'type' => NotificationTypeEnum::BOOKS->value,
            'icon' => 'check-stroked-16',
            'template' => 'book_completed',
        ];
    }

    /**
     * @param array $args
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
     * @param array $args
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
                return $lib?->favorites?->first()?->user;
            });
    }
}
