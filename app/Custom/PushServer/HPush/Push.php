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
    private $color;

    public function __construct($params)
    {
        $this->title   = array_get($params,   'title', '这是一条mipush推送消息');
        $this->desc    = array_get($params,  'title', '这是一条mipush推送消息');
        $this->payload = array_get($params,  'payload', '{"test":1,"ok":"It\'s a string"}');
        $this->token   = array_get($params, 'registrationId');
        $this->type    = 1;
        $this->color   = '#AACCDD';
        $this->intent  = '#Intent;compo=com.rvr/.Activity;S.W=U;end';
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
                        "intent"  => $this->intent,
                    ]
                ]
            ],
            "token" => [$this->token]
        ];

        $application = new Application(env('HW_APPID'), env('HW_APPSECRET'), env('HW_TOKEN_SERVER'), env('HW_PUSH_SERVER'));
        $result = $application->push_send_msg($message);
        Log::info('HPush result:', $result);
        return $result;

        //$pushMsg = new TestPushMsgCommon();
        //$pushMsg->sendPushMsgMessageByMsgType(Constants::PUSHMSG_NOTIFICATION_MSG_TYPE);
        //return $pushMsg->sendPushMsgRealMessage(json_decode($message));


    }
}