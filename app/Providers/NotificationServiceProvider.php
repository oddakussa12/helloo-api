<?php
namespace App\Providers;

use Fenos\Notifynder\Senders\OnceSender;
use Fenos\Notifynder\Senders\MultipleSender;
use Fenos\Notifynder\NotifynderServiceProvider;
use App\Custom\Notifynder\Senders\SingleSender;
use App\Custom\Notifynder\Resolvers\ModelResolver;
use App\Custom\Notifynder\Managers\SenderManager;
use App\Custom\Notifynder\Managers\NotifynderManager;
use App\Custom\Notifynder\Senders\JpushNotificationSender;

class NotificationServiceProvider extends NotifynderServiceProvider
{

    public function registerSenders()
    {
        app('notifynder')->extend('sendSingle', function (array $notifications) {
            return new SingleSender($notifications);
        });

        app('notifynder')->extend('sendMultiple', function (array $notifications) {
            return new MultipleSender($notifications);
        });

        app('notifynder')->extend('sendOnce', function (array $notifications) {
            return new OnceSender($notifications);
        });

        app('notifynder')->extend('sendWithJpush', function($notification) {
            return new JpushNotificationSender($notification);
        });
    }
    protected function bindNotifynder()
    {
        $this->app->singleton('notifynder', function ($app) {
            return new NotifynderManager(
                $app['notifynder.sender']
            );
        });
    }

    /**
     * Bind Notifynder resolver.
     *
     * @return void
     */
    protected function bindResolver()
    {
        $this->app->singleton('notifynder.resolver.model', function () {
            return new ModelResolver();
        });
    }

    protected function bindSender()
    {
        $this->app->singleton('notifynder.sender', function () {
            return new SenderManager();
        });
    }

}