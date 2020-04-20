<?php

namespace App\Events;


class UserProfileLikeEvent
{


    private $likeUser;

    private $user;

    private $like;

    /**
     * Create a new event instance.
     *
     * @param $likeUser
     * @param $user
     * @param $like
     */
    public function __construct($likeUser , $user , $like)
    {
        $this->likeUser = $likeUser;
        $this->user = $user;
        $this->like = $like;
    }

    public function getLikeUser()
    {
        return $this->likeUser;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getLike()
    {
        return $this->like;
    }

}
