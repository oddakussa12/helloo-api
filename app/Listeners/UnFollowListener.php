<?php

/**
 * @Author: Dell
 * @Date:   2019-10-17 19:57:57
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-10-17 20:04:26
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
     * @param UnFollowEvent $event
     * @return void
     */
    public function handle(UnFollow $event)
    {
        $object = $event->getObject();
        $object->refresh();
        notify_remove([1] , $object);
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