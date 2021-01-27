<?php

namespace App\Custom\DingNotice;

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
            \Log::info('ding' , array($app['config']['ding']));
            return new DingTalk($app['config']['ding']);
        });
    }

}
