<?php

namespace Books\Notifications\Classes\Events;

use Books\Comments\Models\Comment;
use Books\Notifications\Classes\NotificationTypeEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class CommentReplied extends BaseEvent
{
    public string $eventName = 'Ответ на комментарий';

    public string $eventDescription = 'Если пользователю оставившему комментарий ответили, то ему приходит уведомление. Если пользователю написали под его комментарием, но ответили не ему, то уведомление не приходит.';

    /**
     * @return array
     */
    public static function defaultParams(): array
    {
        return [
            'type' => NotificationTypeEnum::BOOKS->value,
            'icon' => 'pencil-line-stroked-24',
            'template' => 'comment_replied',
        ];
    }

    /**
     * @param  array  $args
     * @param $eventName
     * @return array
     */
    public static function makeParamsFromEvent(array $args, $eventName = null): array
    {
        /**
         * @var Comment $comment
         */
        $comment = Arr::get($args, 0);
        $comment->load(['parent' => fn($p) => $p->withTrashed()]);
        return array_merge(
            static::defaultParams(),
            [
                'comment' => $comment,
                'recipients' => static::getRecipients([$comment]),
            ],
        );
    }

    /**
     * @param  array  $args
     * @return Collection|null
     */
    public static function getRecipients(array $args): ?Collection
    {
        $comment = Arr::get($args, 0);

        // README: возвращаем именно такую коллекцию, а не collect() ибо во втором случае ошибка сериализации
        return new \October\Rain\Database\Collection([
            $comment->parent?->profiler?->master, // отвечаем на комментарий профилю, с которого был написан комментарий
        ]);
    }
}
