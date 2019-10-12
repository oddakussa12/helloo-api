<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PostViewCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $postView;


    /**
     * Create a new event instance.
     *
     * @param $postView
     */
    public function __construct($postView)
    {
        //
        $this->postView = $postView;
    }

    public function getPostView()
    {
        return $this->postView;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
