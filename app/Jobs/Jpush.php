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
        Log::info('commonPush handle __construct start');

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
        Log::info('commonPush handle __construct end');
    }

    /**
     * Execute the job.
     *
     * @return bool
     */
    public function handle()
    {
        Log::info('commonPush handle start');
        $device = DB::table('devices')->where('user_id', $this->userId)->orderBy('id', 'desc')->first();
        Log::info('commonPush handle device: '. serialize($device));

        if(empty($device)) return false;

        Log::info('commonPush handle device_type:'.$device->device_type.' device_country:'.$device->device_country. " TYPE: ".$device->device_register_type);

        if ($device->device_type==2) {
            if ($device->device_register_type == 'fcm') {
                Log::info('commonPush handle NpushService1');
                NpushService::commonPush($device, $this->formName, $this->userId, $this->type, $this->content);

            } else if(in_array(strtolower($device->device_phone_model), $this->deviceBrand)) {
                Log::info('commonPush handle NpushService2');
                NpushService::commonPush($device, $this->formName, $this->userId, $this->type, $this->content);

            } else{
                Log::info('commonPush handle JpushService2');
                JpushService::commonPush($device, $this->formName ,$this->userId ,$this->type , $this->content , $this->app , $this->version);
            }
        } else{
            Log::info('commonPush handle JpushService2');
            JpushService::commonPush($device, $this->formName ,$this->userId ,$this->type , $this->content , $this->app , $this->version);

        }
    }

}
