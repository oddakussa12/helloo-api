<?php
namespace App\Services;

use App\Custom\PushServer\PushServer;
use App\Jobs\Jpush;
use Illuminate\Support\Facades\Log;

class NPushService
{
    /**
     * android 通过 别名 给单个设备或多个设备推送消息
     * @param $params
     * @return array
     */
    public static function androidPush($params)
    {
        return (new PushServer($params))->send();
    }

    public static function commonPush($device, $fromName, $toUserId, $type='like')
    {
        if(empty($toUserId)) return;

        $title = $fromName.' '.self::getTitle($type , $device->device_language);
        $data = [
            'deviceCountry'  => $device->device_country,
            'registerType'   => $device->device_register_type,
            'deviceBrand'    => !empty($device->device_phone_model) ? strtolower($device->device_phone_model) : '',
            'title'          => $title,
            'content'        => $title,
            'platform'       => $device->device_type,
            'builderId'      => 1,
            'extras'         => ['type'=>$type, 'url'=>self::getPushUrl($type), 'title'=>$title],
            'type'           => 2,
            'registrationId' => $device->device_registration_id
        ];

        return self::androidPush($data);
    }

    /**
     * @param $language
     * @param $device
     * @param $fromName
     * @param string $type
     * @param string $value 帖子 post_uuid 或 话题名称
     * @return array
     * 同语言批量发送
     */
    public static function batchPush($language, $device, $fromName, $type='like', $value='')
    {
        $title = $fromName.' '.self::getTitle($type , $language);
        $data = [
            'registerType'   => $device->device_register_type,
            'deviceBrand'    => !empty($device->device_phone_model) ? strtolower($device->device_phone_model) : '',
            'title'          => $title,
            'content'        => $title,
            'platform'       => $device->device_type,
            'builderId'      => 1,
            'extras'         => ['type'=>$type, 'url'=>self::getPushUrl($type), 'title'=>$title, 'value'=>$value],
            'type'           => 2,
            'registrationId' => $device->device_registration_id
        ];

        return self::androidPush($data);
    }

    public static function getTitle($type = 'privateMessage' , $lang='en')
    {
        return JpushService::getTitle($type, $lang);
    }

    public static function getPushUrl($type = 'follow')
    {
        return JpushService::getPushUrl($type);
    }

}