<?php
/**
Copyright 2020. Huawei Technologies Co., Ltd. All rights reserved.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
 */

/**
 * function: read config file and make it applicaitonable
 */
namespace App\Custom\PushServer\HPush\push_admin;

class PushConfig
{
    // ORDINAL APP
    public $HW_APPID;
    public $HW_APPSECRET;
    public $HW_PUSH_TOKEN_ARR;
    public $APN_PUSH_TOKEN_ARR; 
    public $WEBPUSH_PUSH_TOKEN_ARR;
    
    // FAST APP
    public $HW_FAST_APPID;
    public $HW_FAST_APPSECRET;
    public $HW_FAST_PUSH_TOKEN;
    
    public $HW_TOKEN_SERVER;
    public $HW_PUSH_SERVER;
    public $HW_TOPIC_SUBSCRIBE_SERVER;
    public $HW_TOPIC_UNSUBSCRIBE_SERVER;
    public $HW_TOPIC_QUERY_SUBSCRIBER_SERVER;

    public $HW_DEFAULT_LOG_LEVEL = 3;

    private $log_read_flag = false;

    private function __construct()
    {
        if ($this->log_read_flag == false) {

            $this->HW_APPID = env('HW_APPID');

            $this->HW_APPSECRET = env('HW_APPSECRET');

            $this->HW_TOKEN_SERVER = env('HW_TOKEN_SERVER');

            $this->HW_PUSH_SERVER = env('HW_PUSH_SERVER');

            $this->HW_TOPIC_SUBSCRIBE_SERVER = env('HW_TOPIC_SUBSCRIBE_SERVER');

            $this->HW_TOPIC_UNSUBSCRIBE_SERVER = env('HW_TOPIC_UNSUBSCRIBE_SERVER');

            $this->HW_TOPIC_QUERY_SUBSCRIBER_SERVER = env('HW_TOPIC_QUERY_SUBSCRIBER_SERVER');

            $this->HW_PUSH_TOKEN_ARR = env('HW_PUSH_TOKEN_ARR');

            $this->APN_PUSH_TOKEN_ARR = env('APN_PUSH_TOKEN_ARR');

            $this->WEBPUSH_PUSH_TOKEN_ARR = env('WEBPUSH_PUSH_TOKEN_ARR');

            $this->HW_DEFAULT_LOG_LEVEL = env('HW_DEFAULT_LOG_LEVEL');

            $this->HW_FAST_APPID = env('HW_FAST_APPID');
            $this->HW_FAST_APPSECRET = env('HW_FAST_APPSECRET');

            $this->HW_FAST_PUSH_TOKEN = env('HW_FAST_PUSH_TOKEN');

            $this->log_read_flag = true;

        }
    }

    public static function getSingleInstance()
    {
        static $obj;
        if (! isset($obj)) {
            $obj = new PushConfig();
        }
        return $obj;
    }
}

