<?php

namespace App\Events;

use App\Models\User;
use Jenssegers\Agent\Agent;

class SignupEvent
{

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
        return $this->user->makeVisible('user_ip_address');
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
}
