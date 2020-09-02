<?php
namespace App\Custom\PushServer\HPush;

use App\Custom\PushServer\HPush\push_admin\Application;
use App\Custom\PushServer\HPush\push_admin\Constants;
use App\Custom\PushServer\HPush\push_admin\PushConfig;
use Illuminate\Support\Facades\Log;


class Push
{
    private $title;
    private $desc;
    private $payload;
    private $type;
    private $token;
    private $image;
    private $intent;
    private $extras;
    private $color;

    public function __construct($params)
    {
        $this->title   = array_get($params,   'title');
        $this->desc    = array_get($params,  'title');
        $this->payload = array_get($params,  'payload', '{"test":1,"ok":"It\'s a string"}');
        $this->token   = array_get($params, 'registrationId');
        $this->extras  = array_get($params,  'extras');
        $this->type    = 1;
        $this->color   = '#AACCDD';
        $this->intent  = 'intent://com.yooul/deeplink?#Intent;scheme=pushscheme;launchFlags=0x4000000;S.type=';
        $this->image   = 'https=>//res.vmallres.com/pimages//common/config/logo/SXppnESYv4K11DBxDFc2_0.png';
    }

    public function send()
    {
        $str2 = 2;
        $message = [
            "notification" => [
                "title"    => $this->title,
                "body"     => $this->desc,
                "image"    => $this->image,
            ],
            "android" => [
                "notification"=> [
                    "color"       => $this->color,
                    "click_action"=> [
                        "type"    => $this->type,
                        "intent"  => $this->intent.$this->extras['type'].";end",
                    ]
                ]
            ],
            "token" => [$this->token]
        ];

        $application = new Application(config('push.huawei.appId'), config('push.huawei.secret'), config('push.huawei.token_server'), config('push.huawei.push_server'));
        $result = $application->push_send_msg($message);

        $result = is_object($result) ? json_decode(json_encode($result), true) : $result;
        Log::info('HPush result:', $result);
        return $result;

        //$pushMsg = new TestPushMsgCommon();
        //$pushMsg->sendPushMsgMessageByMsgType(Constants::PUSHMSG_NOTIFICATION_MSG_TYPE);
        //return $pushMsg->sendPushMsgRealMessage(json_decode($message));


    }
}