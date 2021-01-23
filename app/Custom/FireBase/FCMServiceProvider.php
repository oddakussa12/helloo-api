<?php

namespace App\Custom\FireBase;

use Illuminate\Support\ServiceProvider;
use App\Custom\FireBase\Sender\FCMGroup;
use App\Custom\FireBase\Sender\FCMSender;

class FCMServiceProvider extends ServiceProvider
{
    protected $defer = true;


    public function register()
    {
        $this->app->singleton('fcm.client', function ($app) {
            return (new FCMManager($app))->driver();
        });

        $this->app->bind('fcm.group', function ($app) {
            $client = $app[ 'fcm.client' ];
            $url = $app[ 'config' ]->get('fcm.http.server_group_url');

            return new FCMGroup($client, $url);
        });

        $this->app->bind('fcm.sender', function ($app) {
            $client = $app[ 'fcm.client' ];
            $url = $app[ 'config' ]->get('fcm.http.server_send_url');

            return new FCMSender($client, $url);
        });
    }

    public function provides()
    {
        return ['fcm.client', 'fcm.group', 'fcm.sender'];
    }
}
