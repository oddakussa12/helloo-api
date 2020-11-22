<?php
namespace App\Providers;

use RongCloud\RongCloud;
use Illuminate\Support\ServiceProvider;

class RongCloudServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;



    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('rcloud', function ($app) {
            $config = $app->config->get('latrell-rcloud');
            \Log::error($config);
            \Log::error($config);
            $server_url = array_random($config['server_url']);
            return new RongCloud($config['app_key'] , $config['app_secret'] , $server_url);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'rcloud'
        ];
    }
}
