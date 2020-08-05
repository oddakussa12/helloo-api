<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Friend implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    private $senderId;

    /**
     * @var string
     */
    private $targetId;

    /**
     * @var string
     */
    private $content;

    /**
     * @var string
     */
    private $objectName;


    public function __construct($senderId , $targetId , $objectName , $content)
    {
        $this->senderId = $senderId;
        $this->targetId = $targetId;
        $this->content = $content;
        $this->objectName = $objectName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        app('rcloud')->getMessage()->Person()->send(array(
            'senderId'=> $this->senderId,
            'targetId'=> $this->targetId,
            "objectName"=>$this->objectName,
            'content'=>\json_encode($this->content)
        ));
    }
}
