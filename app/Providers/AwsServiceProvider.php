<?php namespace App\Providers;

use Aws\Sdk;
use Aws\DoctrineCacheAdapter;
use App\Custom\Doctrine\Common\Cache\ApcuCache;
use Aws\Credentials\CredentialProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

/**
 * AWS SDK for PHP service provider for Laravel applications
 */
class AwsServiceProvider extends ServiceProvider
{
    const VERSION = '3.6.0';

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap the configuration
     *
     * @return void
     */
    public function boot()
    {

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $cache = new DoctrineCacheAdapter(new ApcuCache);
        $provider = CredentialProvider::defaultProvider();
        $cachedProvider = CredentialProvider::cache($provider, $cache);
        $defaultConfig['credentials'] = $cachedProvider;
        $this->app->singleton('aws', function ($app) use ($defaultConfig){
            $config = $app->make('config')->get('aws');
            $config = array_merge($config , $defaultConfig);
            return new Sdk($config);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['aws'];
    }

}
