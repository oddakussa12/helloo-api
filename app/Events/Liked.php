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

//    private $post_like_temp_num;


    public function __construct($user , $object , $type=1)
    {
        $this->user = $user;
        $this->object = $object;
        $this->type = $type;
//        $this->post_like_temp_num = request()->input('post_like_temp_num' , 0);
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

//    public function getTmpLikeNum()
//    {
//        return $this->post_like_temp_num;
//    }

}
