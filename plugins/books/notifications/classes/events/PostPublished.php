<?php

namespace Books\Notifications\Classes\Events;

use Books\Book\Models\Book;
use Books\Collections\classes\CollectionEnum;
use Books\Collections\Models\Lib;
use Books\Notifications\Classes\NotificationTypeEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class PostPublished extends BaseEvent
{
    public string $eventName = 'Новая публикация в блоге';

    public string $eventDescription = 'Автор, на которого подписан пользователь, разместил новую запись в блоге.';

    /**
     * @return array
     */
    public static function defaultParams(): array
    {
        return [
            'type' => NotificationTypeEnum::BLOG->value,
            'icon' => 'blog-stroked-32',
            'template' => 'post_published',
        ];
    }

    /**
     * @param  array  $args
     * @param $eventName
     * @return array
     */
    public static function makeParamsFromEvent(array $args, $eventName = null): array
    {
        $profile = Arr::get($args, 0);
        $post = Arr::get($args, 1);

        return array_merge(
            static::defaultParams(),
            [
                'profile' => $profile,
                'post' => $post,
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
        $profile = Arr::get($args, 0);

        return $profile
            ?->subscribers()
            ->settingsEnabledBlogPostNotifications()
            ->get();
    }
}
