<?php

namespace Books\Notifications\Classes\Events;

use Books\Notifications\Classes\NotificationTypeEnum;
use Books\Profile\Models\Profile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class SystemMessage extends BaseEvent
{

    public string $eventName = 'Системное сообщение';

    public static function defaultParams(): array
    {
        return [
            'type' => NotificationTypeEnum::SYSTEM->value,
            'icon' => 'info-stroked-24',
            'template' => 'system_message',
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
         * @var \Books\Book\Models\SystemMessage
         */
        $message = Arr::get($args, 0);
        $recipients = Arr::get($args, 1);
        return array_merge(
            static::defaultParams(),
            [
                'message_id' => $message->id,
                'text' => $message->text,
                'recipients' => $recipients ?? static::getRecipients($args)
            ],
        );
    }


    public static function getRecipients(array $args): ?Collection
    {
        return Profile::all();
    }
}
