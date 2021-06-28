<?php

namespace App\Providers;

use Aws\Credentials\CredentialProvider;
use Aws\Credentials\Credentials;
use Aws\DoctrineCacheAdapter;
use App\Custom\Doctrine\Common\Cache\ApcuCache;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\ServiceProvider;


class ElasticSearchManagerServiceProvider extends  ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    public function register()
    {
        $this->app->bind('elastic-search', function ($app) {
            $config = $app->config->get('elastic-search');
            return ClientBuilder::create()
                ->setHosts([$config])->build();
        });

    }

    public function provides()
    {
        return ['elastic-search'];
    }
}
