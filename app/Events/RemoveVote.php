<?php

/*
 * This file is part of the overtrue/laravel-like.
 *
 * (c) overtrue <anzhengchao@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace App\Events;


class RemoveVote
{

    private $user;

    private $object;

    private $relation;
    /**
     * @var int
     */
    private $type=1;



    public function __construct($user , $object , $relation , $type=1)
    {
        $this->user = $user;
        $this->object = $object;
        $this->relation = $relation;
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

    public function getRelation()
    {
        return $this->relation;
    }

    public function getType()
    {
        return $this->type;
    }


}
