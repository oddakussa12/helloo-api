<?php

/**
 * @Author: Dell
 * @Date:   2019-10-17 19:57:57
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-10-18 10:12:40
 */
namespace App\Listeners;


use App\Events\UnFollow;
use App\Traits\CachableUser;

class UnFollowListener
{
    use CachableUser;
    /**
     * Create the event listener.
     *
     * @param $event
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param UnFollow $event
     * @return void
     */
    public function handle(UnFollow $event)
    {
        $object = $event->getObject();
        $follower = $event->getFollower();
        notify_remove(1 , $object , $follower);
        $this->updateUserFollowMeCount($object->getKey() , -1);
        $this->updateUserMyFollowCount($follower->getKey() , -1);
    }
}