<?php

namespace App\Messages;

use Illuminate\Support\Facades\Redis;
use Overtrue\EasySms\Strategies\OrderStrategy;
use App\Custom\EasySms\Contracts\GatewayInterface;
use App\Custom\EasySms\Gateways\YunxinCustomGateway;
use App\Custom\EasySms\Gateways\AliyunCustomGateway;

class ForgetPasswordMessage extends Message
{
    protected $code;
    protected $strategy = OrderStrategy::class;
    protected $gateways = ['aliYunCustom'];

    public function __construct($code)
    {
        $this->code = $code;
    }

    // 定义直接使用内容发送平台的内容
    public function getContent(GatewayInterface $gateway = null)
    {
        return sprintf('Your Lovbee APP security code is %s. You are trying to change the login password. Please keep your account information in a safe place.', $this->code);
    }

    // 定义使用模板发送方式平台所需要的模板 ID
    public function getTemplate(GatewayInterface $gateway = null)
    {
        if($gateway instanceof AliyunCustomGateway)
        {
            return 'SMS_205884521';
        }elseif ($gateway instanceof YunxinCustomGateway)
        {
            return '14872716';
        }
        return '';
    }

    // 模板参数
    public function getData(GatewayInterface $gateway = null)
    {
        return [
            'sign_name'=>'Lovbee',
            'code'=>$this->code
        ];
    }

    public function afterSend($phone , $code='')
    {
        $phone = ltrim($phone , '+');
        $code = empty($code)?$this->code:$code;
        $key = $key = 'helloo:account:service:account-reset-password-sms-code:'.$phone;
        Redis::set($key, $code);
        Redis::expire($key,config('common.user_reset_pwd_sms_wait_time'));
    }
}