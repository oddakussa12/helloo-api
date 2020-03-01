<?php

namespace App\Listeners;

use App\Events\UserProfileRevokeLikeEvent;

class UserProfileRevokeLikeListener
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
     * @param UserProfileRevokeLikeEvent $event
     * @return void
     */
    public function handle(UserProfileRevokeLikeEvent $event)
    {
        $user = $event->getUser();
        $user->decrement('user_profile_like_num');
    }

}
