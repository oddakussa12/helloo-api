<?php

namespace App\Providers;

use App\LaravelLocalization\LaravelLocalization;
use Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider as ServiceProvider;

class LaravelLocalizationServiceProvider extends ServiceProvider
{

    /**
     * Registers app bindings and aliases.
     */
    protected function registerBindings()
    {
        $this->app->singleton(LaravelLocalization::class, function () {
            return new LaravelLocalization();
        });

        $this->app->alias(LaravelLocalization::class, 'laravellocalization');
    }

}
