<?php

namespace Books\Notifications\Classes\Events;

use Books\Blog\Models\Post;
use Books\Book\Models\Book;
use Books\Notifications\Classes\NotificationTypeEnum;
use Books\Profile\Models\Profile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class CommentCreated extends BaseEvent
{
    public string $eventName = 'Комментарий к книге';

    public string $eventDescription = 'Пользователь оставил комментарий к книге автора.';

    /**
     * @return array
     */
    public static function defaultParams(): array
    {
        return [
            'type' => NotificationTypeEnum::BOOKS->value,
            'icon' => 'pencil-line-stroked-24',
            'template' => 'comment_created',
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
                'comment' => Arr::get($args, 0),
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
        $comment = Arr::get($args, 0);

        return match (get_class($comment->commentable)) {

            /**
             * Комментарий к книге
             */
            Book::class => $comment
                ->commentable
                ->authors
                ->filter(static function ($author) use ($comment): bool {
                    return $author->profile->user_id !== $comment->user_id;
                })
                ->transform(static function ($author) {
                    return $author->profile;
                }),

            /**
             * Комментарий к профилю
             */
            Profile::class => (new Collection([$comment->commentable]))
                ->filter(function ($profile) use ($comment) {
                    return $profile->user->id !== $comment->user->id;
                }),

            /**
             * Комментарий к публикации в блоге
             */
            Post::class => (new Collection([$comment->commentable->profile]))
                ->filter(function ($profile) use ($comment) {
                    return $profile->user->id !== $comment->user->id;
                }),
        };
    }
}
