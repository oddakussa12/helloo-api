<?php

namespace App\Jobs;

use App\Services\NPushService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;
use Illuminate\Bus\Queueable;
use App\Services\JpushService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Mpush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $type;
    public $formName;
    public $userId;
    public $content;
    public $version=0;
    public $app;
    private $deviceList;
    private $language;
    private $postOrTopic;

    public function __construct($type, $formName, $language, $deviceList, $postOrTopic='', $content='')
    {
        $this->type       = $type;
        $this->formName   = $formName;
        $this->content    = $content;
        $this->language   = $language;
        $this->deviceList = $deviceList;
        $this->app        = 'android';
        $this->postOrTopic= $postOrTopic;
        Log::info(__FILE__);
    }

    /**
     * Execute the job.
     *
     * @return bool
     */
    public function handle()
    {
        Log::info(__CLASS__. ' '.__FUNCTION__);

        $device = $this->deviceList;
        if(empty($device)) return false;
        NpushService::batchPush($this->language, $this->deviceList, $this->formName, $this->type, $this->postOrTopic);
    }

}
