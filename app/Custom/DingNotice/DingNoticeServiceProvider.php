<?php

namespace App\Custom\DingNotice;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class DingNoticeServiceProvider extends ServiceProvider
{

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerLaravelBindings();
    }


    /**
     * Register Laravel bindings.
     *
     * @return void
     */
    protected function registerLaravelBindings()
    {
        $this->app->singleton(DingTalk::class, function ($app) {
            return new DingTalk($app['config']['ding']);
        });
    }

}
