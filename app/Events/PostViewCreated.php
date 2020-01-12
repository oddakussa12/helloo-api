<?php

namespace App\Events;


class PostViewCreated
{

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
}
