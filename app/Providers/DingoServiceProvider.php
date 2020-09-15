<?php

namespace App\Providers;

use App\Exceptions\ApiHandler as ExceptionHandler;
use Dingo\Api\Provider\DingoServiceProvider as DingoServiceProviders;

class DingoServiceProvider extends DingoServiceProviders
{
    /**
     * Register the exception handler.
     *
     * @return void
     */
    protected function registerExceptionHandler()
    {
        $this->app->singleton('api.exception', function ($app) {
            return new ExceptionHandler($app['Illuminate\Contracts\Debug\ExceptionHandler'], $this->config('errorFormat'), $this->config('debug'));
        });
    }
}
