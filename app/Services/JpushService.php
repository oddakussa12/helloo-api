<?php
namespace App\Services;

use Log;
use JPush\Client;
use Illuminate\Support\Facades\DB;

class JpushService
{
    /**
     * 初始化 JPushClient
     * @param $app
     * @return Client
     */
    public static function newJpushClient($app)
    {
        if ($app == 'android') {
            $appKey = config('jpush.android_app_key');
            $master = config('jpush.android_master_secret');
        } elseif ($app == 'ios') {
            $appKey = config('jpush.ios_app_key');
            $master = config('jpush.ios_master_secret');
        } else {
            return null;
        }
        return new Client($appKey, $master);
    }

    /**
     * android 或 ios 通过 别名 给单个设备或多个设备推送消息
     * @param $params
     * @return array
     */
    public static function androidOrIosPush($params)
    {
        // 推送平台
        $platform = array_get($params, 'platform')==1?'ios':'android';
        // 推送标题
        $title = array_get($params, 'title');
        // 推送内容
        $content = array_get($params, 'content');
        // 通知栏样式 ID
        $builderId = array_get($params, 'builderId');
        // 附加字段 (可用于给前端返回，进行其他业务操作，例如：返回orderId，用于点击通知后跳转到订单详情页面)
        $extras = array_get($params, 'extras');
        // 推送目标 (别名)
        $alias = array_get($params, 'alias');
        // 推送目标 (注册ID)
        $registrationId = array_get($params, 'registrationId');
        // 推送类型 (1-别名 2-注册id 3-全部(ios 或 android))
        $type = array_get($params, 'type');

        // 返回一个推送 Payload 构建器
        $push = self::newJpushClient($platform)->push();

        $push->setPlatform($platform);
        switch ($type) {
            // 通过别名推送
            case 1:
                $push->addAlias($alias);
                break;
            // 通过注册 ID 推送
            case 2:
                $push->addRegistrationId($registrationId);
                break;
            // 推送全部(android 或 ios)
            case 3:
                $push->addAllAudience();
                break;
        }

        $push->androidNotification($content, [ // android 通知
            "title" => $title,
            "builder_id" => $builderId,
            "extras" => $extras,
        ])->iosNotification($content, [ // ios 通知
            "sound" => "sound", // 通知提示声音，如果无此字段，则此消息无声音提示；
            "badge" => "+1", // 应用角标（APP右上角的数字）0 清除 默认 +1
            "extras" => $extras
        ])->options([ // 推送参数
            'apns_production' => config('jpush.environment') // APNs 是否生产环境 (ios)
        ]);

        try {
            $response = $push->send();
            if ($response['http_code'] != 200) {
                Log::info('push_error',
                    compact('response', 'type', 'platform', 'alias', 'registrationId', 'title', 'content')
                );
            }
//        else{
//            Log::info('push_success',
//                compact('response', 'type', 'platform', 'alias', 'registrationId', 'title', 'content')
//            );
//        }
            return $response;

        }catch (\Exception $e){
            \Log::info('Exception:', [$e->getCode(), $e->getMessage()]);
        }


    }

    public static function privateMessagePush($device, $userId , $content)
    {
        if(!empty($userId)&&!empty($content))
        {
            if(!empty($device)&&!empty($device->device_registration_id))
            {
                $data = array(
                    'platform'=>$device->device_type,
                    'builderId'=>1,
                    'extras'=>array('type'=>'privatechat' , 'user_id'=>$userId),
                    'type'=>2,
                    'registrationId'=>$device->device_registration_id
                );
                if($device->device_type==1)
                {
                    $data['content'] = trans('notifynder.user.private_message' , [] , $device->device_language);
                }else{
                    $data['title'] = trans('notifynder.user.private_message' , [] , $device->device_language);
                    $data['content'] = $content;
                }
                self::androidOrIosPush($data);
            }
        }
    }

    public static function commonPush($device, $fromName , $toId, $type = 'like', $content='', $app='web', $version = 0)
    {
        $device->device_registration_id = substr($device->device_registration_id,0, 19); //极光超过19位会报错
        if(!empty($toId)) {
            if($type=='privateMessage') {
                self::privateMessagePush($device, $toId , $content);
            }else{
                if(!empty($device)&&!empty($device->device_registration_id))
                {
                    $title = $fromName.' '.self::getTitle($type , $device->device_language);
                    $data = array(
                        'title'     => $title,
                        'content'   => $title,
                        'platform'  => $device->device_type,
                        'builderId' => 1,
                        'extras'    => ['type'=>$type , 'url'=>self::getPushUrl($type) , 'title'=>$title],
                        'type'      => 2,
                        'registrationId'=> $device->device_registration_id
                    );

                    self::androidOrIosPush($data);
                }
            }
        }
    }

    public static function getTitle($type='privateMessage', $lang='en')
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
        if (in_array($lang, $zhCNArray)) {
            $lang = 'zh-CN';
        } elseif (in_array($lang, $zhTWArray)) {
            $lang = 'zh-TW';
        } else {
            $lang = explode('_', $lang);
            $lang = $lang[0];
        }
        switch ($type)
        {
            case 'like':
                $title = trans('notifynder.user.like' , [] , $lang);
                break;
            case 'post_like':
                $title = trans('notifynder.user.post_like' , [] , $lang);
                break;
            case 'comment':
                $title = trans('notifynder.user.comment' , [] , $lang);
                break;
            case 'publish_post':
                $title = trans('notifynder.user.publish.post' , [] , $lang);
                break;
            case 'publish_topic':
                $title = trans('notifynder.user.publish.topic' , [] , $lang);
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
        if (basename(base_path())!=config('common.app_dir')) {
            $host = 'http://'.config('common.front_domain.h5_test');
        } else {
            $host = 'https://'.config('common.front_domain.h5');
        }
        switch ($type)
        {
            case 'like':
                $url = $host.'/inbox/mylikes';
                break;
            case 'post_like': // 点赞帖子
            case 'publish_post': // 发布帖子
            case 'publish_topic': // 发布话题
            case 'comment': // 评论帖子
                $url = $host.'/inbox/myreplies';
                break;
            default:
                $url = $host.'/followers';
                break;
        }
        return $url;
    }

}