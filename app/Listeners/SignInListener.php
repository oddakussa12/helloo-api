<?php

namespace App\Listeners;

use Carbon\Carbon;
use App\Events\SignInEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;


class SignInListener implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue , SerializesModels;

    public $queue = 'helloo_{user_sign_in}';
    /**
     * Handle the event.
     *
     * @param SignInEvent $event
     * @return void
     */
    public function handle(SignInEvent $event)
    {
        //获取事件中保存的信息
        $user = $event->getUser();
        $agent = $event->getAgent();
        $geo = $event->getGeo();
        $ip = $event->getIp();
        $time = $event->getTime();
        //登录信息
        $sign_in_info = [
            'ip' => $ip,
            'user_id' => $user->user_id
        ];

        //包含的方法获取ip地理位置

        $sign_in_info['isocode'] = strval($geo->iso_code);
        $sign_in_info['country'] = strval($geo->country);
        $sign_in_info['state'] = strval($geo->state_name);
        $sign_in_info['city'] = strval($geo->city);
        $sign_in_info['lat'] = strval($geo->lat);
        $sign_in_info['lon'] = strval($geo->lon);
        $sign_in_info['timezone'] = strval($geo->timezone);
        $sign_in_info['continent'] = strval($geo->continent);


        // jenssegers/agent 的方法来提取agent信息
        $sign_in_info['version'] = strval($agent->getHttpHeader('HellooVersion')); //版本
        $sign_in_info['device_id'] = strval($agent->getHttpHeader('deviceId')); //设备ID

        $platform = $agent->platform();
        $sign_in_info['platform'] = strval($platform); //操作系统
        $sign_in_info['platform_version'] = strval($agent->version($platform)); //操作系统
        $sign_in_info['lang'] = implode(',', $agent->languages()); //语言
        $sign_in_info['created_at'] = $time;
        DB::table('signin_infos')->insert($sign_in_info);

    }

}
