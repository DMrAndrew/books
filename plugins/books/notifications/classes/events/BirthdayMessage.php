<?php

namespace Books\Notifications\Classes\Events;

use Books\Notifications\Classes\NotificationTypeEnum;
use Books\Profile\Models\Profile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RainLab\User\Models\User;

class BirthdayMessage extends BaseEvent
{

    public string $eventName = 'Системное сообщение';

    public static function defaultParams(): array
    {
        return [
            'type' => NotificationTypeEnum::SYSTEM->value,
            'icon' => 'info-stroked-24',
            'template' => 'birthday_message',
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
        $recipients = Arr::get($args, 0);

        return array_merge(
            static::defaultParams(),
            [
                'recipients' => $recipients ?? static::getRecipients($args)
            ],
        );
    }


    public static function getRecipients(array $args): ?Collection
    {
        return User::leftJoin('books_profile_profiles as profile', 'users.id', '=', 'profile.id')
            ->leftJoin('books_book_authors as author', 'author.profile_id', '=', 'profile.id')
            ->whereMonth('users.birthday', Carbon::now()->format('m'))
            ->whereDay('users.birthday', Carbon::now()->format('d'))
            ->distinct()
            ->get('users.id');
    }
}
