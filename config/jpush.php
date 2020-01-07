<?php
return [
    'android_app_key' => env('JPUSH_APP_KEY' , '06095173eb3c1ad2f48e90da'),
    'android_master_secret' => env('JPUSH_APP_MASTER_SECRET' , '4a3a7dda0a19c69e03b0d241'),

    'ios_app_key' => env('JPUSH_IOS_KEY' , 'd2bcfc8d23991f0d3fe142ac'),
    'ios_master_secret' => env('JPUSH_IOS_MASTER_SECRET' , '4c286194f47c7abda4e95c6d'),

    // 环境 true-生产环境 false-开发环境
    'environment' => env('JPUSH_APNS_PRODUCTION', true),
];