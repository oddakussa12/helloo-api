<?php

namespace App\Events;


class UserProfileRevokeLikeEvent
{


    private $user;

    private $likeUser;

    /**
     * Create a new event instance.
     *
     * @param $user
     */
    public function __construct($likeUser , $user)
    {
        $this->likeUser = $likeUser;
        $this->user = $user;
    }

    public function getLikeUser()
    {
        return $this->likeUser;
    }


    public function getUser()
    {
        return $this->user;
    }

}
