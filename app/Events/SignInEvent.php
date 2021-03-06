<?php

namespace App\Events;

use App\Models\User;
use Carbon\Carbon;
use Jenssegers\Agent\Agent;

class SignInEvent
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
     * @var string IP地址信息
     */
    protected $addresses;


    /**
     * 实例化事件时传递这些信息
     * @param $user
     * @param $addresses
     */
    public function __construct($user , $addresses)
    {
        $this->user = $user;
        $this->agent = new Agent;
        $this->ip = $addresses;
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

    public function getGeo()
    {
        return geoip($this->ip);
    }

    public function getTime()
    {
        return Carbon::now()->toDateTimeString();
    }
}
