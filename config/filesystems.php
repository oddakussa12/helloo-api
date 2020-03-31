<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "s3", "rackspace"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
        ],
        'qn_default' => [
            'driver'     => 'qn_default',
            'access_key' => env('QINIU_ACCESS_KEY', 'Rzzn0G7I6K38FsVHHkW9o48ZWZsuOpPvRQGzZyLS'),
            'secret_key' => env('QINIU_SECRET_KEY', 'BrMK7FBrj7wvzA7KC7q4fBsaYBznA2p_6PWT6ku5'),
            'bucket'     => env('QINIU_BUCKET', 'idwebother'),
            'domain'     => env('QINIU_DOMAIN', 'pv4wf6zk2.bkt.clouddn.com/'), // or host: https://xxxx.clouddn.com
        ],
        'qn_avatar' => [
            'driver'     => 'qn_avatar',
            'access_key' => env('QINIU_ACCESS_KEY', 'Rzzn0G7I6K38FsVHHkW9o48ZWZsuOpPvRQGzZyLS'),
            'secret_key' => env('QINIU_SECRET_KEY', 'BrMK7FBrj7wvzA7KC7q4fBsaYBznA2p_6PWT6ku5'),
            'bucket'     => env('QINIU_BUCKET', 'idwebother'),
            'domain'     => env('QINIU_DOMAIN', 'https://qnidwebother.mmantou.cn/'), // or host:
        ],
        'qn_image' => [
            'driver'     => 'qn_image',
            'access_key' => env('QINIU_ACCESS_KEY', 'Rzzn0G7I6K38FsVHHkW9o48ZWZsuOpPvRQGzZyLS'),
            'secret_key' => env('QINIU_SECRET_KEY', 'BrMK7FBrj7wvzA7KC7q4fBsaYBznA2p_6PWT6ku5'),
            'bucket'     => env('QINIU_BUCKET', 'pythonidimage'),
            'domain'     => env('QINIU_DOMAIN', 'https://qnidimage.mmantou.cn/'), // or host: https://xxxx.clouddn.com
        ],
        'qn_avatar_sia' => [
            'driver'     => 'qn_avatar_sia',
            'access_key' => env('QINIU_ACCESS_KEY', 'Rzzn0G7I6K38FsVHHkW9o48ZWZsuOpPvRQGzZyLS'),
            'secret_key' => env('QINIU_SECRET_KEY', 'BrMK7FBrj7wvzA7KC7q4fBsaYBznA2p_6PWT6ku5'),
            'bucket'     => env('QINIU_BUCKET', 'idwebother-sia'),
            'domain'     => env('QINIU_DOMAIN', 'https://qnwebothersia.mmantou.cn/'), // or host:
        ],
        'qn_image_sia' => [
            'driver'     => 'qn_image_sia',
            'access_key' => env('QINIU_ACCESS_KEY', 'Rzzn0G7I6K38FsVHHkW9o48ZWZsuOpPvRQGzZyLS'),
            'secret_key' => env('QINIU_SECRET_KEY', 'BrMK7FBrj7wvzA7KC7q4fBsaYBznA2p_6PWT6ku5'),
            'bucket'     => env('QINIU_BUCKET', 'idimage'),
            'domain'     => env('QINIU_DOMAIN', 'https://qnidyooulimage.mmantou.cn/'), // or host: https://xxxx.clouddn.com
        ],
        'qn_video_sia' => [
            'driver'     => 'qn_video_sia',
            'access_key' => env('QINIU_ACCESS_KEY', 'Rzzn0G7I6K38FsVHHkW9o48ZWZsuOpPvRQGzZyLS'),
            'secret_key' => env('QINIU_SECRET_KEY', 'BrMK7FBrj7wvzA7KC7q4fBsaYBznA2p_6PWT6ku5'),
            'bucket'     => env('QINIU_BUCKET', 'idvideo'),
            'domain'     => env('QINIU_DOMAIN', 'https://qnidyooulvideo.mmantou.cn/'), // or host: https://xxxx.clouddn.com
        ],

    ],

];
