<?php

namespace App\Custom\Queue\Duplicators;

use Ramsey\Uuid\Uuid;
use App\Custom\Queue\Contracts\Queue\Duplicator;

class Unique implements Duplicator
{
    /**
     * Generate a unique deduplication id.
     *
     * This duplicator should be used for queues that should treat all messages
     * as unique, even if the payload is identical to another message.
     *
     * @param string $payload
     * @param string $queue
     *
     * @return string
     */
    public function generate(string $payload, string $queue)
    {
        return Uuid::uuid4()->toString();
    }
}
