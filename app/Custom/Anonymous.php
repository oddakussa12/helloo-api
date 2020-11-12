<?php


namespace App\Custom;


class Anonymous
{
    private $callback;

    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    public function __invoke()
    {
        $callback = $this->callback;
        return $callback();
    }
}