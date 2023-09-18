<?php

namespace Books\Notifications\Classes\Events;

use Books\Notifications\Classes\NotificationTypeEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class BookCreated extends BaseEvent
{
    public string $eventName = 'Публикация книги';

    public string $eventDescription = 'Автор, на которого подписан пользователь опубликовал(а) новую книгу.';

    /**
     * @return array
     */
    public static function defaultParams(): array
    {
        return [
            'type' => NotificationTypeEnum::BOOKS->value,
            'icon' => 'book-stroked-24',
            'template' => 'book_created',
        ];
    }

    /**
     * @param  array  $args
     * @param $eventName
     * @return array
     */
    public static function makeParamsFromEvent(array $args, $eventName = null): array
    {
        $book = Arr::get($args, 0);

        $authors = $book->orderedAuthors();
        $authors->load('profile');

        return array_merge(
            static::defaultParams(),
            [
                'book' => $book,
                'authors' => $authors,
                'recipients' => static::getRecipients([$authors]),
            ],
        );
    }

    /**
     * Каждому подписчику отправляем уведомление о том, что его автор опубликовал книгу
     * Если подписчик подписан на обоих авторов из соавторства, уведомление должно придти одно
     * Автор владелец - в приоритете
     *
     * @param  array  $args
     * @return Collection|null
     */
    public static function getRecipients(array $args): ?Collection
    {
        $authors = Arr::get($args, 0);

        /**
         * add dynamic property of `subscribedForAuthor`
         */
        $authorsSubscribers = collect();
        $authorsSubscribersKeys = [];

        $authors->each(function ($author) use (&$authorsSubscribers, &$authorsSubscribersKeys) {

            $authorSubscribers = $author?->profile?->subscribers;

            if ($authorSubscribers) {
                $authorSubscribers->each(function($subscriber) use ($author, &$authorsSubscribers, &$authorsSubscribersKeys) {
                    if (!in_array($subscriber->id, $authorsSubscribersKeys)) {
                        $subscriber->subscribedForAuthor = $author->id;
                        $authorsSubscribersKeys[] = $subscriber->id;

                        $authorsSubscribers->push($subscriber);
                    }
                });
            }
        });

        return $authorsSubscribers;
    }
}
