<?php
namespace App\Custom\PushServer\MiPush;

use xmpush\Builder;
use xmpush\HttpBase;
use xmpush\Sender;
use xmpush\Constants;
use xmpush\Stats;
use xmpush\Tracer;
use xmpush\Feedback;
use xmpush\DevTools;
use xmpush\Subscription;
use xmpush\TargetedMessage;
use xmpush\Region;

include_once(dirname(__FILE__) . '/autoload.php');
class Push
{
    private $title;
    private $desc;
    private $payload;
    private $token;
    private $extra;
    private $intent;

    public function __construct($params)
    {
        $this->title   = array_get($params, 'title');
        $this->desc    = array_get($params, 'title');
        $this->payload = array_get($params, 'payload');
        $this->extra   = array_get($params, 'extras');
        $this->token   = array_get($params, 'registrationId');
        $this->intent  = 'intent://com.yooul/deeplink?#Intent;scheme=pushscheme;launchFlags=0x4000000;S.type=';

        // 常量设置必须在new Sender()方法之前调用
        Constants::setPackage(config('push.xiaomi.package'));
        Constants::setSecret(config('push.xiaomi.secret'));
    }


    //指定 单独的token 推送
    public function Send()
    {
        $message = $this->sendBase();
        return (new Sender())->send($message, $this->token)->getRaw();
    }

    //topic 发送
    public function SendTopic(){
        $extras  = json_decode($this->extra,true);
        $message = $this->sendBase();
        return (new Sender())->broadcast($message,$extras['tags'])->getRaw();
    }

    /**
     * @return mixed
     * 全量推送
     */
    public function SendAll()
    {
        $message = $this->sendBase();
        return (new Sender())->broadcastAll($message)->getRaw();
    }

    public function sendBase()
    {
        $message = new Builder();
        $message->title($this->title);  // 通知栏的title
        $message->description($this->desc); // 通知栏的description
        $message->passThrough(0);  // 这是一条通知栏消息，如果需要透传，把这个参数设置成1,同时去掉title和descption两个参数
        $message->payload($this->payload); // 携带的数据，点击后将会通过客户端的receiver中的onReceiveMessage方法传入。
        $message->extra(Builder::notifyForeground, 1); // 应用在前台是否展示通知，如果不希望应用在前台时候弹出通知，则设置这个参数为0

        $message->extra(Builder::notifyEffect, 2);    // 此处设置预定义点击行为，1 为打开app,2为打开应用内的activity
        $message->extra(Builder::intentUri, $this->intent.$this->extra['type'].";end"); // 打开应用内activity必须添加此参数

        $message->extra('extra', $this->extra);
        $message->notifyId(99999); // 通知类型。最多支持0-4 5个取值范围，同样的类型的通知会互相覆盖，不同类型可以在通知栏并存   左边的官方文档，实际测试，随便取值0-9999999都可以。
        $message->build();
        return $message;
    }


}



?>
