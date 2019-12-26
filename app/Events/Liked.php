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

class Liked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $user;

    private $object;
    /**
     * @var int
     */
    private $type;


    public function __construct($user , $object , $type=1)
    {
        $this->user = $user;
        $this->object = $object;
        $this->type = $type;
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

}
