<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Services\JpushService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Jpush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $type;
    public $formName;
    public $userId;
    public $content;

    public function __construct($type , $formName , $userId , $content='')
    {
        $this->type = $type;
        $this->formName = $formName;
        $this->userId = $userId;
        $this->content = $content;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        JpushService::commonPush($this->formName ,$this->userId ,$this->type , $this->content);
    }

}
