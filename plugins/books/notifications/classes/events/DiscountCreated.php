<?php

namespace Books\Notifications\Classes\Events;

use Books\Collections\classes\CollectionEnum;
use Books\Collections\Models\Lib;
use Books\Notifications\Classes\NotificationTypeEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class DiscountCreated extends BaseEvent
{
    public string $eventName = 'Скидка на книгу';

    public string $eventDescription = 'Автор установил скидку на произведение';

    /**
     * @return array
     */
    public static function defaultParams(): array
    {
        return [
            'type' => NotificationTypeEnum::DISCOUNTS->value,
            'icon' => 'sale-stroked-24',
            'template' => 'edition_discounted',
        ];
    }

    /**
     * @param  array  $args
     * @param $eventName
     * @return array
     */
    public static function makeParamsFromEvent(array $args, $eventName = null): array
    {
        $discount = Arr::get($args, 0);
        $edition = $discount->edition;

        return array_merge(
            static::defaultParams(),
            [
                'author' => $edition?->book->author,
                'book' => $edition?->book,
                'old_price' => $edition?->priceTag()->initialPrice(),
                'new_price' => $edition?->priceTag()->price(),
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
        $book = Arr::get($args, 0)?->edition?->book;
        $bookId = $book?->id;

        return Lib::query()
            ->book($book)
            ->whereIn('type', [
                CollectionEnum::INTERESTED->value,
                CollectionEnum::READING->value,
            ])
            ->with('favorites.user')
            ->whereHas('favorites', function ($query) use ($bookId) {
                return $query->whereHas('user', function ($query) use ($bookId) {

                    /** Настройки уведомлений в ЛК */
                    $query->settingsEnabledBookDiscountNotifications()

                    /** Книга еще не куплена */
                    ->whereDoesntHave('ownedBooks', function ($q) use ($bookId) {
                        $q->where('ownable_id', $bookId);
                    });
                });
            })
            ->get()
            ->transform(static function (Lib $lib) {
                return $lib?->favorites->first()?->user;
            });
    }
}
