<?php

namespace App\Custom\Queue\Duplicators;

use App\Custom\Queue\Contracts\Queue\Duplicator;

class Content implements Duplicator
{
    /**
     * Generate a deduplication id based on the hash of the message.
     *
     * This duplicator should be used for queues that should treat
     * identical payloads as duplicate messages.
     *
     * @param string $payload
     * @param string $queue
     *
     * @return string
     */
    public function generate(string $payload, string $queue)
    {
        return hash('sha256', $payload);
    }
}
