<?php

namespace Books\Notifications\Classes\Events;

use RainLab\Notify\Classes\EventBase;

class TestEvent extends EventBase
{
    public $conditions = [

    ];

    public function eventDetails()
    {
        return [
            'name' => 'Test Events',
            'description' => 'Test Events',
            'group' => 'user',
        ];
    }

    public function defineParams()
    {
        return [
            'name' => [
                'title' => 'Name',
                'label' => 'Name of the events',
            ],
            // ...
        ];
    }

    public static function makeParamsFromEvent(array $args, $eventName = null)
    {
        return array_get($args, 0);
    }
}
