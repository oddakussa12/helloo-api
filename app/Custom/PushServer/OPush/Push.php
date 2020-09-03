<?php
namespace App\Custom\PushServer\OPush;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use oppoPush\oppoPush;

include_once(dirname(__FILE__).'/oppo_push/autoload.php');

class Push
{
    private $client;
    private $authToken;
    private $title;
    private $desc;
    private $payload;
    private $token;
    private $type;
    private $intent;
    private $extra;

    public function __construct($params)
    {
        $this->title   = array_get($params,   'title');
        $this->desc    = array_get($params,  'title');
        $this->payload = array_get($params,  'payload');
        $this->token   = array_get($params, 'registrationId');
        $this->extra   = array_get($params, 'extras');
        $this->type    = 1;
//        $this->intent  = 'intent://com.yooul/deeplink?#Intent;scheme=pushscheme;launchFlags=0x4000000;S.type=';
        $this->intent  = 'com.yooul.oppopush';

        //实例oppo
        $this->client = new oppoPush(config('push.oppo.appKey'), config('push.oppo.masterSecret')); // AppKey 与 MasterSecret(非 AppSecret)
        try {
            $key = 'oppo_push_auth_token';
            $authToken = Redis::get($key);
            if (!$authToken) {
                $authToken  = $this->client->getAuthToken();// 有效期24小时
                $expireTime = $this->client->getAuthTokenExpiresTime(); // 获取 auth_token 过期时间
                Redis::set($key, $authToken, 'nx', 'ex', $expireTime - time());
            }
            $this->authToken = $authToken;

        } catch (\Exception $e) {
            Log::error(__CLASS__. '__construct Exception: code:'.$e->getCode().' message: '.$e->getMessage());
        }

    }

    public function Send()
    {
        $this->client->setTitle($this->title)->setContent($this->desc)->setAuthToken($this->authToken);
        $this->client->setIntent($this->intent);// 打开应用内页的 intent action
        //$this->client->setActionUrl('http://www.xxx.com');// 打开网页
        $this->client->setActionParameters(json_encode($this->extra)); // 打开应用内页或网页时传递的参数 (数组或json类型)
        $this->client->addRegistrationId($this->token);     // 添加需要发送设备的 registration_id, 最多 1000 个
        try {
           return $this->client->broadcastByRegId();              // registration_id 推送
        } catch (\Exception $e) {
            Log::info('OPPO PUSH Exception: code:' .$e->getCode().' msg:'.$e->getMessage());
            return false;
        }
    }

    /**
     * @return mixed
     * 全量推送
     */
    public function SendAll()
    {
        $this->client->setTitle($this->title)
            ->setContent($this->desc)
            ->setAuthToken($this->authToken);
       return $this->client->broadcastAll(); // 全量用户推送
    }

}