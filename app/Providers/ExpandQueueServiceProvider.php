<?php

namespace App\Providers;

use App\Custom\Queue\Duplicators\Sqs;
use App\Custom\Queue\Duplicators\Unique;
use App\Custom\Queue\Duplicators\Content;
use Illuminate\Queue\QueueServiceProvider;
use App\Custom\Queue\Connectors\SqsConnector;
use App\Custom\Queue\Connectors\SqsFifoConnector;


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

    public function register()
    {
        parent::register();
        $this->registerDuplicators();
        $this->app->afterResolving('queue', function ($manager) {
            $manager->addConnector('sqs-fifo', function () {
                return new SqsFifoConnector;
            });
        });
    }

    /**
     * Register the default duplicator methods.
     *
     * @return void
     */
    public function registerDuplicators()
    {
        foreach (['Unique', 'Content', 'Sqs'] as $duplicator) {
            $this->{"register{$duplicator}Duplicator"}();
        }
    }

    /**
     * Register the unique registerUniqueDuplicator to treat all messages as unique.
     *
     * @return void
     */
    public function registerUniqueDuplicator()
    {
        $this->app->bind('queue.sqs-fifo.duplicator.unique', Unique::class);
    }

    /**
     * Register the content duplicator to treat messages with the same payload as duplicates.
     *
     * @return void
     */
    public function registerContentDuplicator()
    {
        $this->app->bind('queue.sqs-fifo.duplicator.content', Content::class);
    }

    /**
     * Register the SQS duplicator for queues with ContentBasedDeduplication enabled on SQS.
     *
     * @return void
     */
    public function registerSqsDuplicator()
    {
        $this->app->bind('queue.sqs-fifo.duplicator.sqs', Sqs::class);
    }

}
