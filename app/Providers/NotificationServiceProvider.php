<?php
namespace App\Providers;

use App\Jobs\Jpush;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function boot() {
        \Notifynder::extend('sendWithJpush', function($notification) {
            Jpush::dispatch($notification->type , $notification->user_name , $notification->user_id , $notification->content)->onQueue('op_jpush');
        });
    }
}