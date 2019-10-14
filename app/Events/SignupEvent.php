<?php

namespace App\Events;

use Jenssegers\Agent\Agent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SignupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var User 用户模型
     */
    protected $user;

    /**
     * @var Agent Agent对象
     */
    protected $agent;

    /**
     * @var string IP地址
     */
    protected $ip;


    /**
     * 实例化事件时传递这些信息
     */
    public function __construct($user , $addresses)
    {
        $this->user = $user;
        $this->agent = new Agent;
        $this->ip = request()->ip();
        $this->addresses = $addresses;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getAgent()
    {
        return $this->agent;
    }

    public function getIp()
    {
        return $this->ip;
    }

    public function getAddresses()
    {
        return $this->addresses;
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
