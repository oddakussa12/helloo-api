<?php

namespace App\Custom\Queue\Duplicators;

use App\Custom\Queue\Contracts\Queue\Duplicator;

class Sqs implements Duplicator
{
    /**
     * Do not generate a deduplication id.
     *
     * This duplicator should be used for queues where Amazon's
     * ContentBasedDeduplication features is enabled on SQS.
     *
     * @param string $payload
     * @param string $queue
     *
     * @return bool
     */
    public function generate(string $payload, string $queue)
    {
        return false;
    }
}
