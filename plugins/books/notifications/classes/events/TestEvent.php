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

    /**
     * @param  array  $args
     * @param $eventName
     * @return mixed|void
     */
    public static function makeParamsFromEvent(array $args, $eventName = null)
    {  //TODO сформировать body уведомления и первичный список получателей, который будет фильтроваться условиями
        return array_get($args, 0);
    }
}
