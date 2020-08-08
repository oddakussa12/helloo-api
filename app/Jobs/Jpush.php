<?php

namespace App\Jobs;

use App\Services\NPushService;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;
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
    public $version=0;
    public $app='web';
    public $deviceBrand = ['huawei', 'honer', 'xiaomi', 'oppo', 'vivo'];

    public function __construct($type , $formName , $userId , $content='')
    {
        $this->type = $type;
        $this->formName = $formName;
        $this->userId = $userId;
        $this->content = $content;
        $agent = new Agent();
        if($agent->match('Yooul'))
        {
            $this->version = (string)$agent->getHttpHeader('YooulVersion');
            if($agent->match('YooulAndroid'))
            {
                $this->app = 'android';
            }elseif ($agent->match('YoouliOS'))
            {
                $this->app = 'ios';
            }else{
                $this->app = 'web';
            }
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $device = DB::table('devices')->where('user_id', $this->userId)->orderBy('device_updated_at', 'desc')->first();
        if(empty($device)) return false;


        if($this->app == 'android' && $device->device_country != 'zh-CN') {
            $fcm;
            NpushService::commonPush($device, $this->formName, $this->userId, $this->type, $this->content);

        } elseif($this->app == 'android' && $device->device_country == 'zh-CN' && in_array(strtolower($device->device_phone_model), $this->deviceBrand)) { //国内华为等
            NpushService::commonPush($device, $this->formName, $this->userId, $this->type, $this->content);
        } else {
            JpushService::commonPush($device, $this->formName ,$this->userId ,$this->type , $this->content , $this->app , $this->version);
        }
    }

}
