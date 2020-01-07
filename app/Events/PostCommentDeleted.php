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

class PostCommentDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;



    private $user;
    private $object;


    public function __construct($user , $object)
    {
        $this->user = $user;
        $this->object = $object;
    }

    public function getObject()
    {
        return $this->object;
    }

    public function getUser()
    {
        return $this->user;
    }



}
