<?php

namespace Books\Notifications\Classes\Events;

use Illuminate\Support\Collection;
use RainLab\Notify\Classes\EventBase;

abstract class BaseEvent extends EventBase
{
    public string $eventName = '';
    public string $eventDescription = '';

    /**
     * @return string[]
     */
    public function eventDetails(): array
    {
        return [
            'group' => 'user',
            'name' => $this->eventName,
            'description' => $this->eventDescription,
        ];
    }

    /**
     * @return array
     */
    abstract public static function defaultParams(): array;

    /**
     * @param array $args
     * @return Collection|null
     */
    abstract public static function getRecipients(array $args): ?Collection;
}
