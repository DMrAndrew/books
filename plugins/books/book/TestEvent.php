<?php

namespace Books\Book;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class TestEvent extends \Event implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    public function broadcastOn()
    {
        return new PrivateChannel('messenger.profile.2');
    }

    public function broadcastAs(){
        return 'new.message';
    }

    public function broadcastWith()
    {
        return [
            'message' => [
                'body'            => rand(0,100),
            ],
        ];
    }
}
