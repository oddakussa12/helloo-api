<?php

namespace App\Custom\Queue\Duplicators;

use Closure;
use App\Custom\Queue\Contracts\Queue\Duplicator;

class Callback implements Duplicator
{
    /**
     * The user defined callback function to generate the deduplication id.
     *
     * @var \Closure
     */
    protected $callback;

    /**
     * Create a new duplicator instance.
     *
     * @param  \Closure  $callback
     *
     * @return void
     */
    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Generate a deduplication id using the defined callback function.
     *
     * This duplicator can be used to allow a developer to quickly generate
     * a custom duplicator using a Closure, without having to implement
     * a completely new duplicator object.
     *
     * @param string $payload
     * @param string $queue
     *
     * @return string
     */
    public function generate(string $payload, string $queue)
    {
        return call_user_func($this->callback, $payload, $queue);
    }
}
