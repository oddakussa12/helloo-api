<?php
namespace App\Custom\PushServer\HPush;

use App\Custom\PushServer\HPush\push_admin\Application;
use App\Custom\PushServer\HPush\push_admin\Constants;
use App\Custom\PushServer\HPush\push_admin\PushConfig;


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
        //$pushMsg = new TestPushMsgCommon();
        //$pushMsg->sendPushMsgMessageByMsgType(Constants::PUSHMSG_NOTIFICATION_MSG_TYPE);
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

        //return $pushMsg->sendPushMsgRealMessage(json_decode($message));

        $config  = PushConfig::getSingleInstance();
        $application = new Application($config->HW_APPID, $config->HW_APPSECRET, $config->HW_TOKEN_SERVER, $config->HW_PUSH_SERVER);
        $application->push_send_msg($message);

    }
}