<?php

return [
    'huawei' => [
        'appId'        => env('HW_APPID'),
        'secret'       => env('HW_APPSECRET'),
        'token_server' => env('HW_TOKEN_SERVER'),
        'push_server'  => env('HW_PUSH_SERVER'),
    ],
    'xiaomi' => [
        'package'      => env('MI_PACKAGE'),
        'secret'       => env('MI_APPSECRET'),

    ],
    'oppo' => [
        'appId'        => env('OPPO_APPID'),
        'appKey'       => env('OPPO_APPKEY'),
        'secret'       => env('OPPO_APPSECRET'),
        'masterSecret' => env('OPPO_MASTERSECRET'),
    ],
    'vivo' => [

    ]
];
