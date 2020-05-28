<?php

namespace App\Listeners;

use App\Traits\CachableUser;
use App\Events\UserProfileLikeEvent;

class UserProfileLikeListener
{
    use CachableUser;

    /**
     * Create the event listener.
     *
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param UserProfileLikeEvent $event
     * @return void
     */
    public function handle(UserProfileLikeEvent $event)
    {
        $likeUser = $event->getLikeUser();
        $user = $event->getUser();
        $like = $event->getLike();
        $user->increment('user_profile_like_num');
        $this->storeProfileLikeMe($likeUser->user_id , $user->user_id  , $like->created_at->timestamp);
    }

}
