<?php

namespace Books\Notifications\Classes\Events;

use Books\Notifications\Classes\NotificationTypeEnum;
use Books\Profile\Models\Profile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class SystemCertificateMessage extends BaseEvent
{

    public string $eventName = 'Системное сообщение';

    public static function defaultParams(): array
    {
        return [
            'type' => NotificationTypeEnum::SYSTEM->value,
            'icon' => 'gift-stroked-24',
            'template' => 'system_certificate',
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
        $amount = Arr::get($args, 0);
        $anonymity = Arr::get($args, 2);
        $sender = Arr::get($args, 3);
        $certificate_id = Arr::get($args, 4);
        return array_merge(
            static::defaultParams(),
            [
                'amount' => $amount,
                'anonymity' => $anonymity,
                'sender' => $sender,
                'certificate_id' => $certificate_id,
                'recipients' => static::getRecipients($args)
            ],
        );
    }


    public static function getRecipients(array $args): ?Collection
    {
        $recipient = Arr::get($args, 1);
        return $recipient->user->get();
    }
}
