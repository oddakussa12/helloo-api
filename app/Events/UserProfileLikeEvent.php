<?php

namespace App\Events;


class UserProfileLikeEvent
{


    private $user;

    /**
     * Create a new event instance.
     *
     * @param $user
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    public function getIp()
    {
        return getRequestIpAddress();
    }


    public function getUser()
    {
        return $this->user;
    }

}
