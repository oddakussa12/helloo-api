<?php

namespace App\Listeners;

use App\Events\UserProfileLikeEvent;

class UserProfileLikeListener
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
     * @param PostViewEvent $event
     * @return void
     */
    public function handle(UserProfileLikeEvent $event)
    {
        $user = $event->getUser();
        $user->increment('user_profile_like_num');
    }

}
