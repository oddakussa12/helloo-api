<?php

/**
 * @Author: Dell
 * @Date:   2019-10-16 14:00:49
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-10-17 17:23:42
 */
namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class Follow
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