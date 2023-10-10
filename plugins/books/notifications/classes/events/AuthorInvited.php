<?php

namespace Books\Notifications\Classes\Events;

use Books\Notifications\Classes\NotificationTypeEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Log;

class AuthorInvited extends BaseEvent
{
    public string $eventName = 'Соавторство';

    public string $eventDescription = 'Пользователь (автор) указал Вас соавтором при публикации книги';

    /**
     * @return array
     */
    public static function defaultParams(): array
    {
        return [
            'type' => NotificationTypeEnum::BOOKS->value,
            'icon' => 'pencil-line-stroked-24',
            'template' => 'author_invited',
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
                'coauthor' => Arr::get($args, 0),
                'owner' => Arr::get($args, 1),
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
        $coAuthor = Arr::get($args, 0);

        // README: возвращаем именно такую коллекцию, а не collect() ибо во втором случае ошибка сериализации
        return new \October\Rain\Database\Collection([
            $coAuthor->profile,
        ]);
    }
}
