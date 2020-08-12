<?php
namespace App\Services;

use App\Custom\PushServer\PushServer;
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
            'deviceBrand'    => $device->device_phone_model,
            'title'          => $title,
            'content'        => $title,
            'platform'       => $device->device_type,
            'builderId'      => 1,
            'extras'         => ['type'=>$type, 'url'=>self::getPushUrl($type), 'title'=>$title],
            'type'           => 2,
            'registrationId' => $device->device_registration_id
        ];

        Log::info('commonPush deviceInfo: '. $device->device_registration_id);
        Log::info('commonPush data:', $data);
        return self::androidPush($data);
    }

    /**
     * @param $params
     * 批量 推送
     */
    public function batchPush($params)
    {
        
    }


    public static function getTitle($type = 'privateMessage' , $lang='en')
    {
        $lang = strtolower($lang);
        $zhCNArray = array(
            'yue_hans_cn',
            'yue_hans',
            'zh_hant',
            'zh-cn',
            'zh',
        );
        $zhTWArray = array(
            'zh_hant_tw',
            'zh_hant_hk',
            'zh_hant_mo',
            'zh_hans_hk',
            'zh-tw',
            'zh-hk',
        );
        if(in_array($lang , $zhCNArray))
        {
            $lang = 'zh-CN';
        }elseif (in_array($lang , $zhTWArray))
        {
            $lang = 'zh-TW';
        }else
        {
            $lang = explode('_' , $lang);
            $lang = $lang[0];
        }
        switch ($type)
        {
            case 'like':
                $title = trans('notifynder.user.like' , [] , $lang);
                break;
            case 'comment':
                $title = trans('notifynder.user.comment' , [] , $lang);
                break;
            case 'post_comment':
                $title = trans('notifynder.user.post_comment' , [] , $lang);
                break;
            case 'follow':
                $title = trans('notifynder.user.follow' , [] , $lang);
                break;
            default:
                $title = trans('notifynder.user.private_message' , [] , $lang);
                break;
        }
        return $title;
    }

    public static function getPushUrl($type = 'follow')
    {
        if(basename(base_path())!=config('common.app_dir'))
        {
            $host = 'http://'.config('common.front_domain.h5_test');
        }else{
            $host = 'https://'.config('common.front_domain.h5');
        }
        switch ($type)
        {
            case 'like':
                $url = $host.'/inbox/mylikes';
                break;
            case 'comment':
                $url = $host.'/inbox/myreplies';
                break;
            default:
                $url = $host.'/followers';
                break;
        }
        return $url;
    }

}