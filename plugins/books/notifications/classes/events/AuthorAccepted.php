<?php

namespace Books\Notifications\Classes\Events;

use Books\Notifications\Classes\NotificationTypeEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class AuthorAccepted extends BaseEvent
{
    public string $eventName = 'Принять соавторство';

    public string $eventDescription = 'Если пользователю оставившему комментарий ответили, то ему приходит уведомление. Если пользователю написали под его комментарием, но ответили не ему, то уведомление не приходит.';

    /**
     * @return array
     */
    public static function defaultParams(): array
    {
        return [
            'type' => NotificationTypeEnum::BOOKS->value,
            'icon' => 'pencil-line-stroked-24',
            'template' => 'author_accepted',
        ];
    }

    /**
     * @param  array  $args
     * @param $eventName
     * @return array
     */
    public static function makeParamsFromEvent(array $args, $eventName = null): array
    {
        $author = Arr::get($args, 0);

        return array_merge(
            static::defaultParams(),
            [
                'author' => Arr::get($args, 0),
                'book' => $author?->book,
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
        $author = Arr::get($args, 0);

        // README: уведомляем всех авторов книги, кроме автора из события
        return $author
            ?->book
            ?->authors
            ?->where('profile_id', '!=', $author?->profile_id)
            ?->transform(static function ($author) {
                return $author->profile;
            });
    }
}
