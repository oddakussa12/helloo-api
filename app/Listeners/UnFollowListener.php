<?php

/**
 * @Author: Dell
 * @Date:   2019-10-17 19:57:57
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-10-18 10:12:40
 */
namespace App\Listeners;


use App\Events\UnFollow;

class UnFollowListener
{
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
    }

    /**
     * Handle the event.
     *
     * @param UnFollow $event
     * @param $exception
     * @return void
     */
    public function failed(UnFollow $event, $exception)
    {

    }
}