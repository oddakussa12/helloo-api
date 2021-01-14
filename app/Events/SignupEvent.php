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
     * @var string IP地址信息
     */
    protected $addresses;

    /**
     * @var string 扩展信息
     */
    protected $extend;


    /**
     * 实例化事件时传递这些信息
     * @param $user
     * @param $addresses
     * @param array $extend
     */
    public function __construct($user , $addresses , $extend = array())
    {
        $this->user = $user;
        $this->agent = new Agent;
        $this->ip = $addresses;
        $this->extend = $extend;
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

    public function getExtend()
    {
        return $this->extend;
    }
}
