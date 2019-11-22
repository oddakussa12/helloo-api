<?php

/*
 * This file is part of the overtrue/laravel-like.
 *
 * (c) overtrue <anzhengchao@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace App\Events;
use Illuminate\Broadcasting\PrivateChannel;

class DisLiked
{
    private $user;
    private $object;
    private $type;
    private $relation;

    public function __construct($user , $object , $relation , $type=1)
    {
        $this->user = $user;
        $this->object = $object;
        $this->type = $type;
        $this->relation = $relation;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getObject()
    {
        return $this->object;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getRelation()
    {
        return $this->relation;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
