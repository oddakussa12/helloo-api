<?php

/*
 * This file is part of the overtrue/easy-sms.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Custom\EasySms;

use App\Messages\Message;
use Overtrue\EasySms\EasySms as Sms;
use App\Custom\EasySms\Gateways\Gateway;
use App\Messages\Contracts\MessageInterface;
use App\Custom\EasySms\Contracts\GatewayInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;
use Overtrue\EasySms\Exceptions\InvalidArgumentException;

/**
 * Class EasySms.
 */
class EasySms extends Sms
{
    /**
     * @param string|\Overtrue\EasySms\Contracts\PhoneNumberInterface $number
     *
     * @return \Overtrue\EasySms\Contracts\PhoneNumberInterface|string
     */
    protected function formatPhoneNumber($number)
    {
        if ($number instanceof PhoneNumberInterface) {
            return $number;
        }

        return new PhoneNumber(\trim($number));
    }

    /**
     * @param array|string|MessageInterface $message
     *
     * @return MessageInterface
     */
    protected function formatMessage($message)
    {
        if (!($message instanceof MessageInterface)) {
            if (!\is_array($message)) {
                $message = [
                    'content' => $message,
                    'template' => $message,
                ];
            }

            $message = new Message($message);
        }

        return $message;
    }

    /**
     * Send a message.
     *
     * @param string|array                                       $to
     * @param MessageInterface|array $message
     * @param array                                              $gateways
     *
     * @return array
     *
     * @throws \Overtrue\EasySms\Exceptions\InvalidArgumentException
     * @throws \Overtrue\EasySms\Exceptions\NoGatewayAvailableException
     */
    public function send($to, $message, array $gateways = [])
    {
        $to = $this->formatPhoneNumber($to);
        $message = $this->formatMessage($message);
        $gateways = empty($gateways) ? $message->getGateways() : $gateways;

        if (empty($gateways)) {
            $gateways = $this->config->get('default.gateways', []);
        }

        return $this->getMessenger()->send($to, $message, $this->formatGateways($gateways));
    }

    /**
     * @return Messenger
     */
    public function getMessenger()
    {
        return $this->messenger ?: $this->messenger = new Messenger($this);
    }

    /**
     * Create a gateway.
     *
     * @param string|null $name
     *
     * @return GatewayInterface
     *
     * @throws \Overtrue\EasySms\Exceptions\InvalidArgumentException
     */
    public function gateway($name)
    {
        if (!isset($this->gateways[$name])) {
            $this->gateways[$name] = $this->createGateway($name);
        }

        return $this->gateways[$name];
    }

    /**
     * Create a new driver instance.
     *
     * @param string $name
     *
     * @return GatewayInterface
     *
     * @throws \InvalidArgumentException
     *
     * @throws \Overtrue\EasySms\Exceptions\InvalidArgumentException
     */
    protected function createGateway($name)
    {
        $config = $this->config->get("gateways.{$name}", []);

        if (!isset($config['timeout'])) {
            $config['timeout'] = $this->config->get('timeout', Gateway::DEFAULT_TIMEOUT);
        }

        $config['options'] = $this->config->get('options', []);

        if (isset($this->customCreators[$name])) {
            $gateway = $this->callCustomCreator($name, $config);
        } else {
            $className = $this->formatGatewayClassName($name);
            $gateway = $this->makeGateway($className, $config);
        }

        if (!($gateway instanceof GatewayInterface)) {
            throw new InvalidArgumentException(\sprintf('Gateway "%s" must implement interface %s.', $name, GatewayInterface::class));
        }

        return $gateway;
    }
}
