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

use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;
use Aws\Credentials\Credentials;
use Overtrue\EasySms\Support\Config;
use App\Messages\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Exceptions\GatewayErrorException;

/**
 * Class AwsGateway.
 */
class AwsGateway extends Gateway
{
    /**
     * @param \Overtrue\EasySms\Contracts\PhoneNumberInterface $to
     * @param \Overtrue\EasySms\Contracts\MessageInterface     $message
     * @param \Overtrue\EasySms\Support\Config                 $config
     *
     * @return array
     *
     * @throws \Overtrue\EasySms\Exceptions\GatewayErrorException ;
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config)
    {
        $config = $this->config;
        $awsKey = $config->get('key');
        $awsSecret = $config->get('secret');
        $credentials = new Credentials($awsKey, $awsSecret);
        $smsClient = new SnsClient([
            'region' => 'ap-southeast-1',
            'version' => '2010-03-31',
            'credentials' => $credentials
        ]);
        $message = $message->getContent();
        $phone = $to->getPrefixedIDDCode().$to->getNumber();
        \Log::error($awsKey);
        \Log::error($awsSecret);
        \Log::error($message);
        \Log::error($phone);
        try {
            $result = $smsClient->publish([
                'Message' => $message,
                'PhoneNumber' => $phone
//                'AWS.SNS.SMS.SenderID' =>'Lovbee'
            ]);
            \Log::error($result);
            return $result;
        } catch (AwsException $e) {
            throw new GatewayErrorException($e->getMessage(), $e->getCode());
        }
    }

}
