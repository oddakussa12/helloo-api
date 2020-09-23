<?php

namespace App\Providers;

use Illuminate\Queue\QueueServiceProvider;
use App\Custom\Queue\Connectors\SqsConnector;


class ExpandQueueServiceProvider extends QueueServiceProvider
{
    /**
     * Register the Amazon SQS queue connector.
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerSqsConnector($manager)
    {
        $manager->addConnector('sqs', function () {
            return new SqsConnector;
        });
    }
}
