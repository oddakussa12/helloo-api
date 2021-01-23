<?php

return [
    'driver' => env('FCM_PROTOCOL', 'http'),
    'log_enabled' => env('FCM_LOG_ENABLED', false),

    'http' => [
        'server_key' => env('FCM_SERVER_KEY', 'Your FCM server key'),
        'sender_id' => env('FCM_SENDER_ID', 'Your sender id'),
        'server_send_url' => 'https://fcm.googleapis.com/fcm/send',
        //'server_send_url' => 'https://fcm.googleapis.com/v1/projects/myproject-b5ae1/messages:send',
        'server_group_url' => 'https://android.googleapis.com/gcm/notification',
        'timeout' => 30.0, // in second
    ],
];
