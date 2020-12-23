<?php
namespace App\Providers;

use App\Custom\NEIm\NetEaseIm;
use Illuminate\Support\ServiceProvider;

class NetEaseServiceProvider extends ServiceProvider
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
        $this->app->bind('netEase', function ($app) {
            $config = $app->config->get('netease');
            return new NetEaseIm(array(
                'AppKey'=>$config['app_key'],
                'AppSecret'=>$config['app_secret']
            ));
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
            'netEase'
        ];
    }
}
