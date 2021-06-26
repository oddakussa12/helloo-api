<?php


namespace App\Providers;


use Aws\DoctrineCacheAdapter;
use Aws\Credentials\Credentials;
use Aws\Pinpoint\PinpointClient;
use Aws\Credentials\CredentialProvider;
use Illuminate\Support\ServiceProvider;
use App\Custom\Doctrine\Common\Cache\ApcuCache;

class PinpointServiceProvider extends ServiceProvider
{
    /**
     * 注册服务.
     * @https://learnku.com/articles/6189/laravel-service-provider-detailed-concept
     * @return void
     */
    public function register()
    {
        $config = config('aws.Pinpoint');
        $config = $this->getDefaultConfiguration($config);
        if(empty($config['key'])||empty($config['secret']))
        {
            $cache = new DoctrineCacheAdapter(new ApcuCache);
            $provider = CredentialProvider::defaultProvider();
            $cachedProvider = CredentialProvider::cache($provider, $cache);
            $config['credentials'] = $cachedProvider;
        }
        $this->app->singleton('pinpoint', function ($app) use ($config){
            return new PinpointClient($config);
        });

    }

    protected function getDefaultConfiguration(array $config)
    {
        return array_merge([
            'http' => [
                'timeout' => 60,
                'connect_timeout' => 60,
            ],
        ], $config);
    }



    public function provides()
    {
        return ['pinpoint'];
    }

}