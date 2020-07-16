<?php


namespace App\Listeners;

use App\Traits\CachableUser;
use App\Events\UserUpdatedEvent;

class UserUpdatedListener
{
    use CachableUser;
    /**
     * @param UserUpdatedEvent $event
     * @return void
     */
    public function handle(UserUpdatedEvent $event)
    {
        $user = $event->getUser();
        $this->updateUser($user);
    }
}