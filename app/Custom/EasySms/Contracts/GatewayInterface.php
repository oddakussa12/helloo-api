<?php

/*
 * This file is part of the overtrue/easy-sms.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\Custom\EasySms\Contracts;

use Overtrue\EasySms\Support\Config;
use App\Messages\Contracts\MessageInterface;
use Overtrue\EasySms\Contracts\PhoneNumberInterface;

/**
 * Class GatewayInterface.
 */
interface GatewayInterface
{
    /**
     * Get gateway name.
     *
     * @return string
     */
    public function getName();

    /**
     * Send a short message.
     *
     * @param PhoneNumberInterface $to
     * @param MessageInterface     $message
     * @param Config                 $config
     *
     * @return array
     */
    public function send(PhoneNumberInterface $to, MessageInterface $message, Config $config);
}
