<?php

namespace Books\Notifications\Classes\Events;

use Books\Collections\classes\CollectionEnum;
use Books\Collections\Models\Lib;
use Books\Notifications\Classes\NotificationTypeEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class BookUpdated extends BaseEvent
{
    const DELTA_LENGTH_TRIGGER = 5000;

    public string $eventName = 'Обновление книги';

    public string $eventDescription = 'Автор дописал и опубликовал новую часть книги от 5000 знаков - обновил книгу - которая находится у пользователя в Моей библиотеке во вкладке Читаю сейчас.';

    /**
     * @return array
     */
    public static function defaultParams(): array
    {
        return [
            'type' => NotificationTypeEnum::BOOKS->value,
            'icon' => 'refresh-stroked-24',
            'template' => 'book_updated',
        ];
    }

    /**
     * @param array $args
     * @param $eventName
     * @return array
     */
    public static function makeParamsFromEvent(array $args, $eventName = null): array
    {
        $book = Arr::get($args, 0);
        $author = $book->authors()->owner(true)->first();

        $symbolsCount = (int) Arr::get($args, 1);

        return array_merge(
            static::defaultParams(),
            [
                'book' => Arr::get($args, 0),
                'author' => $author,
                'symbols_count' => $symbolsCount,
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
