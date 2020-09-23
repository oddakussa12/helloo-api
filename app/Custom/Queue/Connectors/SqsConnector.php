<?php

namespace App\Custom\Queue\Connectors;

use Aws\Sqs\SqsClient;
use Illuminate\Support\Arr;
use Aws\DoctrineCacheAdapter;
use Illuminate\Queue\SqsQueue;
use Doctrine\Common\Cache\ApcuCache;
use Aws\Credentials\CredentialProvider;
use Illuminate\Queue\Connectors\ConnectorInterface;

class SqsConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        $config = $this->getDefaultConfiguration($config);

        $cache = new DoctrineCacheAdapter(new ApcuCache);
        $provider = CredentialProvider::defaultProvider();
        $cachedProvider = CredentialProvider::cache($provider, $cache);
        $config['credentials'] = $cachedProvider;
        if (!empty($config['key']) && !empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret']);
        }else{
            unset($config['key']);
            unset($config['secret']);
        }
        return new SqsQueue(
            new SqsClient($config), $config['queue'], $config['prefix'] ?? ''
        );
    }

    /**
     * Get the default configuration for SQS.
     *
     * @param  array  $config
     * @return array
     */
    protected function getDefaultConfiguration(array $config)
    {
        return array_merge([
            'version' => 'latest',
            'http' => [
                'timeout' => 60,
                'connect_timeout' => 60,
            ],
        ], $config);
    }
}
