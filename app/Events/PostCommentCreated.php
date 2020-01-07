<?php

/*
 * This file is part of the overtrue/laravel-like.
 *
 * (c) overtrue <anzhengchao@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class PostCommentCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;



    private $postComment;
    private $post;
    private $user;


    public function __construct($post , $postComment , $user)
    {
        $this->postComment = $postComment;

        $this->post = $post;

        $this->user = $user;

    }

    public function getPostComment()
    {
        return $this->postComment;
    }

    public function getPost()
    {
        return $this->post;
    }

    public function getUser()
    {
        return $this->user;
    }



}
