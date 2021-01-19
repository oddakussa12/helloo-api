<?php

namespace App\Listeners;

use App\Jobs\EscortTalk;
use App\Events\SignupEvent;
use App\Jobs\SignUpAndEvent;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;


class SignupListener implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue , SerializesModels;

    public $queue = 'helloo_{user_sign_up}';
    /**
     * Handle the event.
     *
     * @param SignupEvent $event
     * @return void
     */
    public function handle(SignupEvent $event)
    {
        //获取事件中保存的信息
        $user = $event->getUser();
        $agent = $event->getAgent();
        $geo = $event->getGeo();
        $ip = $event->getIp();
        $extend = $event->getExtend();
        //登录信息
        $signup_info = [
            'signup_ip' => $ip,
            'user_id' => $user->user_id
        ];

        //包含的方法获取ip地理位置

        $signup_info['signup_isocode'] = $geo->iso_code;
        $signup_info['signup_country'] = $geo->country;
        $signup_info['signup_state'] = $geo->state_name;
        $signup_info['signup_city'] = $geo->city;
        $signup_info['signup_lat'] = $geo->lat;
        $signup_info['signup_lon'] = $geo->lon;
        $signup_info['signup_timezone'] = $geo->timezone;
        $signup_info['signup_continent'] = $geo->continent;


        // jenssegers/agent 的方法来提取agent信息
        $signup_info['signup_device'] = $agent->device(); //设备名称
        $browser = $agent->browser();
        $signup_info['signup_browser'] = $browser; //浏览器
        $signup_info['signup_browser_version'] = $agent->version($browser); //浏览器
        $platform = $agent->platform();
        $signup_info['signup_platform'] = $platform; //操作系统
        $signup_info['signup_platform_version'] = $agent->version($platform); //操作系统
        $signup_info['signup_lang'] = implode(',', $agent->languages()); //语言
        //设备类型
        if ($agent->isTablet()) {
            // 平板
            $signup_info['device_type'] = 'tablet';
        } else if ($agent->isMobile()) {
            // 便捷设备
            $signup_info['device_type'] = 'mobile';
        } else if ($agent->isRobot()) {
            // 爬虫机器人
            $signup_info['device_type'] = 'robot';
            $signup_info['device'] = $agent->robot(); //机器人名称
        } else {
            // 桌面设备
            $signup_info['device_type'] = 'desktop';
        }
        isset($extend['featureName'])&&$signup_info['feature_name'] = strval($extend['featureName']);
        isset($extend['countryCode'])&&$signup_info['country_code'] = strtolower(strval($extend['countryCode']));
        isset($extend['latitude'])&&$signup_info['latitude'] = strval($extend['latitude']);
        isset($extend['longitude'])&&$signup_info['longitude'] = strval($extend['longitude']);
        isset($extend['locality'])&&$signup_info['locality'] = strval($extend['locality']);
        isset($extend['adminArea'])&&$signup_info['admin_area'] = strval($extend['adminArea']);
        isset($extend['subAdminArea'])&&$signup_info['sub_admin_area'] = strval($extend['subAdminArea']);
        isset($extend['countryName'])&&$signup_info['country_name'] = strval($extend['countryName']);
        isset($extend['addressLine'])&&$signup_info['address_line'] = strval($extend['addressLine']);
        isset($extend['thoroughfare'])&&$signup_info['thoroughfare'] = strval($extend['thoroughfare']);
        $user->SignupInfo()->create($signup_info);
        SignUpAndEvent::dispatch($user)->onQueue('helloo_{sign_up_and_event}')->delay(now()->addSeconds(60));
        EscortTalk::dispatch($user , $extend)->onQueue('helloo_{escort_talk}')->delay(now()->addSeconds(120));
    }

}
