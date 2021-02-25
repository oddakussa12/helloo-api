<?php

/*
 * This file is part of the overtrue/easy-sms.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Custom\EasySms\Gateways;


use Illuminate\Support\Facades\Log;
use Overtrue\EasySms\Support\Config;
use Overtrue\EasySms\Traits\HasHttpRequest;
use App\Messages\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Exceptions\GatewayErrorException;

/**
 * Class YuntongxunGateway.
 *
 * @see http://www.yuntongxun.com/doc/rest/sms/3_2_2_2.html
 */
class YuntongxunCustomGateway extends Gateway
{
    use HasHttpRequest;

    const ENDPOINT_TEMPLATE = 'https://%s:%s/%s/%s/%s/%s/%s?sig=%s';

    const SERVER_IP = 'app.cloopen.com';

    const DEBUG_SERVER_IP = 'sandboxapp.cloopen.com';

    const DEBUG_TEMPLATE_ID = 1;

    const SERVER_PORT = '8883';

    const SDK_VERSION = '2013-12-26';

    const SUCCESS_CODE = '000000';

    /**
     * @param \Overtrue\EasySms\Contracts\PhoneNumberInterface $to
     * @param App\Messages\Contracts\MessageInterface     $message
     * @param \Overtrue\EasySms\Support\Config                 $config
     *
     * @return array
     *
     * @throws \Overtrue\EasySms\Exceptions\GatewayErrorException ;
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $datetime = date('YmdHis');

        $endpoint = $this->buildEndpoint('SMS', 'TemplateSMS', $datetime, $config);

        $body = [
            'json' => [
                'to' => !\is_null($to->getIDDCode()) ? strval($to->getZeroPrefixedNumber()) : $to->getNumber(),
                'templateId' => (int) ($this->config->get('debug') ? self::DEBUG_TEMPLATE_ID : $message->getTemplate($this)),
                'appId' => $config->get('appId'),
                'datas' => array_values($message->getData($this)),
            ],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json;charset=utf-8',
                'Authorization' => base64_encode($config->get('accountSid').':'.$datetime),
            ],
        ];
        Log::info('$body' ,$body);
        Log::info('$to->getNumber()' ,array($to->getNumber()));
        $result = $this->request('post', $endpoint, $body);
        if (self::SUCCESS_CODE != $result['statusCode']) {
            throw new GatewayErrorException($result['statusCode'], $result['statusCode'], $result);
        }

        return $result;
    }

    /**
     * Build endpoint url.
     *
     * @param string                           $type
     * @param string                           $resource
     * @param string                           $datetime
     * @param \Overtrue\EasySms\Support\Config $config
     *
     * @return string
     */
    protected function buildEndpoint($type, $resource, $datetime, Config $config)
    {
        $serverIp = $this->config->get('debug') ? self::DEBUG_SERVER_IP : self::SERVER_IP;

        $accountType = $this->config->get('is_sub_account') ? 'SubAccounts' : 'Accounts';

        $sig = strtoupper(md5($config->get('accountSid').$config->get('accountToken').$datetime));

        return sprintf(self::ENDPOINT_TEMPLATE, $serverIp, self::SERVER_PORT, self::SDK_VERSION, $accountType, $config->get('accountSid'), $type, $resource, $sig);
    }
}
