<?php

/**
 * @Author: Dell
 * @Date:   2019-10-17 19:56:47
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-10-17 19:57:07
 */
namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class UnFollow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;



    private $object;


    public function __construct($object)
    {
        $this->object = $object;

    }

    public function getObject()
    {
        return $this->object;
    }



}