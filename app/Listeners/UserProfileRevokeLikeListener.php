<?php

namespace App\Listeners;

use App\Traits\CachableUser;
use App\Events\UserProfileRevokeLikeEvent;

class UserProfileRevokeLikeListener
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
     * @param UserProfileRevokeLikeEvent $event
     * @return void
     */
    public function handle(UserProfileRevokeLikeEvent $event)
    {
        $likeUser = $event->getLikeUser();
        $user = $event->getUser();
        $user->decrement('user_profile_like_num');
        $this->storeProfileLikeMe($likeUser->user_id , $user->user_id);
    }

}
