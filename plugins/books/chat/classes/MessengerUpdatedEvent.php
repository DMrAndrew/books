<?php

namespace Books\Chat\Classes;


use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Laravel\Horizon\Contracts\Silenced;
use RTippin\Messenger\Contracts\MessengerProvider;

class MessengerUpdatedEvent extends \Event implements ShouldBroadcastNow, Silenced
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(protected MessengerProvider $provider)
    {
    }

    public function broadcastOn()
    {
        return new PrivateChannel('profile.'.$this->provider->getKey());
    }


    public function broadcastAs()
    {
        return 'notifications';
    }

    public function broadcastWith(): array
    {
        return (new MessengerService($this->provider))->render();
    }
}
