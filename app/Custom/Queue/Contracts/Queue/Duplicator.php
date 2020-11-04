<?php

namespace App\Custom\Queue\Contracts\Queue;

interface Duplicator
{
    /**
     * Generate a deduplication id to determine if a message is a duplicate.
     *
     * @param string $payload
     * @param string $queue
     *
     * @return string|bool
     */
    public function generate(string $payload, string $queue);
}
