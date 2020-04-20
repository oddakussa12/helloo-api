<?php

/**
 * @Author: Dell
 * @Date:   2019-10-17 17:33:46
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-10-17 18:41:59
 */
namespace App\Listeners;

use App\Events\Follow;
use App\Traits\CachableUser;

class FollowListener
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
     * @param Follow $event
     * @return void
     */
    public function handle(Follow $event)
    {
        $object = $event->getObject();
        $follower = $event->getFollower();
        notify('user.following' ,
            array(
                'from'=>$follower->user_id ,
                'to'=>$object->user_id ,
                'extra'=>array(
                    'follow_user_id'=>$follower->user_id,
                    'befollow_user_id'=>$object->user_id,
                ) ,
                'setField'=>array('contact_id' , $object->getKey()),
                'url'=>'/notification/user/'.$follower->user_id.'/userFollow/'.$object->getKey(),
            )
        );
        $this->updateUserFollowMeCount($object->getKey());
        $this->updateUserMyFollowCount($follower->getKey());
    }

}