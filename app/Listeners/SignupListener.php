<?php

namespace App\Listeners;

use App\Events\SignupEvent;
use App\Traits\CachableUser;


class SignupListener
{
    use CachableUser;


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
        $this->updateUserLists($user->user_name , $user->user_email);
        $agent = $event->getAgent();
        $addresses = $event->getAddresses();
        $ip = $addresses->ip;
        //登录信息
        $signup_info = [
            'signup_ip' => $ip,
            'user_id' => $user->user_id
        ];

        //包含的方法获取ip地理位置

        $signup_info['signup_isocode'] = $addresses->iso_code;
        $signup_info['signup_country'] = $addresses->country;
        $signup_info['signup_state'] = $addresses->state_name;
        $signup_info['signup_city'] = $addresses->city;
        $signup_info['signup_lat'] = $addresses->lat;
        $signup_info['signup_lon'] = $addresses->lon;
        $signup_info['signup_timezone'] = $addresses->timezone;
        $signup_info['signup_continent'] = $addresses->continent;


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

        return $user->SignupInfo()->create($signup_info);
    }

}
