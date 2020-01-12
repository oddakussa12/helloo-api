<?php

namespace App\Events;


class PostViewEvent
{


    private $post;

    /**
     * Create a new event instance.
     *
     * @param $post
     */
    public function __construct($post)
    {
        //
        $this->post = $post;
    }

    public function getIp()
    {
        return getRequestIpAddress();
    }


    public function getPost()
    {
        return $this->post;
    }

    public function getUser()
    {
        return auth()->user();
    }
}
