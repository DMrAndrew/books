<?php

namespace Books\Notifications\Classes\Events;
use Books\Book\Classes\BookUtilities;
use Books\Notifications\Classes\NotificationTypeEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
class DeferredApplied extends BaseEvent
{

    public string $eventName = 'Слияние контента книги';

    public string $eventDescription = '';

    /**
     * @return array
     */
    public static function defaultParams(): array
    {
        return [
            'type' => NotificationTypeEnum::BOOKS->value,
            'icon' => 'book-stroked-24',
            'template' => 'deferred_applied',
        ];
    }

    /**
     * @param array $args
     * @param $eventName
     * @return array
     */
    public static function makeParamsFromEvent(array $args, $eventName = null): array
    {
        /**
         * @var \Books\Book\Models\Content
         */
        $content = Arr::get($args, 0);
        $chapter = $content->contentable;
        $book = $chapter->edition->book;
        $comment = Arr::get($args, 1);
        return array_merge(
            static::defaultParams(),
            [
                'content' => $content,
                'type_label' => $content->type->label(),
                'status_label' => $content->status->label(),
                'chapter_title' => BookUtilities::stringToDiDom($chapter->title)->text(),
                'book' => $book,
                'comment' => $comment,
                'recipients' => static::getRecipients($args)
            ],
        );
    }


    public static function getRecipients(array $args): ?Collection
    {
        $content = Arr::get($args, 0);
        return $content->contentable->edition->book->authors->map->profile;
    }
}
