<?php

namespace App\Messages;

use Overtrue\EasySms\Message;
use Overtrue\EasySms\Gateways\YunxinGateway;
use Overtrue\EasySms\Gateways\AliyunGateway;
use Overtrue\EasySms\Strategies\OrderStrategy;
use Overtrue\EasySms\Contracts\GatewayInterface;
use App\Custom\EasySms\Gateways\YunxinCustomGateway;
use App\Custom\EasySms\Gateways\AliyunCustomGateway;

class SignInMessage extends Message
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
        return sprintf('Your Helloo security code is %s. Yours is logging in Helloo!. Please keep your account information in a safe place.', $this->code);
    }

    // 定义使用模板发送方式平台所需要的模板 ID
    public function getTemplate(GatewayInterface $gateway = null)
    {
        if($gateway instanceof AliyunCustomGateway)
        {
            return 'SMS_205443411';
        }elseif ($gateway instanceof YunxinCustomGateway)
        {
            return '14881706';
        }
        return '';
    }

    public function getPhone()
    {

    }

    // 模板参数
    public function getData(GatewayInterface $gateway = null)
    {
        return [
            'code'=>$this->code
        ];
    }
}