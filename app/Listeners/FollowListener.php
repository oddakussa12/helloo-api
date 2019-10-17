<?php

/**
 * @Author: Dell
 * @Date:   2019-10-17 17:33:46
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-10-17 18:41:59
 */
namespace App\Listeners;


use App\Events\Follow;

class FollowListener
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
     * @param FollowEvent $event
     * @return void
     */
    public function handle(Follow $event)
    {
        $object = $event->getObject();
        $object->refresh();
        notify('user.following' ,
            array(
                'from'=>auth()->id() ,
                'to'=>$object->user_id ,
                'extra'=>array(
                    'follow_user_id'=>auth()->id(),
                    'befollow_user_id'=>$object->user_id,
                ) ,
                'setField'=>array('contact_id' , $object->{$object->getKeyName()}),
                'url'=>'/notification/user/'.auth()->id().'/userFollow/'.$object->{$object->getKeyName()},
            )
        );
    }

    /**
     * Handle the event.
     *
     * @param Follow $event
     * @param $exception
     * @return void
     */
    public function failed(Follow $event, $exception)
    {

    }
}