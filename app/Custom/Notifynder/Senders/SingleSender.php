<?php

namespace App\Custom\Notifynder\Senders;

use App\Custom\Notifynder\Models\Notification;
use Fenos\Notifynder\Contracts\SenderContract;
use Fenos\Notifynder\Contracts\SenderManagerContract;

/**
 * Class SingleSender.
 */
class SingleSender implements SenderContract
{
    /**
     * @var \Fenos\Notifynder\Builder\Notification
     */
    protected $notification;

    /**
     * SingleSender constructor.
     *
     * @param array $notifications
     */
    public function __construct(array $notifications)
    {
        $this->notification = array_values($notifications)[0];
    }

    /**
     * Send the single notification.
     *
     * @param SenderManagerContract $sender
     * @return bool
     */
    public function send(SenderManagerContract $sender)
    {
        $model = app('notifynder.resolver.model')->getModel(Notification::class);

        $notify= new $model();

        $notification = $notify->create($this->notification->jsonSerialize());

        return $notification;
    }
}
