<?php


return [
    /*
    |--------------------------------------------------------------------------
    | 内置路由
    |--------------------------------------------------------------------------
    |
    | 如果是 web 应用建议 middleware 为 ['web', ...]
    | 如果是 api 应用建议 middleware 为 ['api', ...]
    |
    */
    'route' => [
        'enable'     => true,
        'prefix'     => 'laravel-sms',
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | 请求间隔
    |--------------------------------------------------------------------------
    |
    | 单位：秒
    |
    */
    'interval' => 60,

    /*
    |--------------------------------------------------------------------------
    | 数据验证管理
    |--------------------------------------------------------------------------
    |
    | 设置从客户端传来的需要验证的数据字段(`field`)
    |
    | - isMobile    是否为手机号字段
    | - enable      是否开启验证
    | - default     默认静态验证规则
    | - staticRules 静态验证规则
    |
    */
    'validation' => [
        'mobile' => [
            'isMobile'    => true,
            'enable'      => true,
            'default'     => 'mobile_required',
            'staticRules' => [
                'mobile_required'     => 'required|zh_mobile',
                'check_mobile_unique' => 'required|zh_mobile|unique:users,mobile',
                'check_mobile_exists' => 'required|zh_mobile|exists:users',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 验证码管理
    |--------------------------------------------------------------------------
    |
    | - length        验证码长度
    | - validMinutes  验证码有效时间长度，单位为分钟
    | - repeatIfValid 如果原验证码还有效，是否重复使用原验证码
    | - maxAttempts   验证码最大尝试验证次数，超过该数值验证码自动失效，0或负数则不启用
    |
    */
    'code' => [
        'length'        => 5,
        'validMinutes'  => 5,
        'repeatIfValid' => false,
        'maxAttempts'   => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | 验证码短信通用内容
    |--------------------------------------------------------------------------
    |
    | 如需缓存配置，则需使用 `Toplan\Sms\SmsManger::closure($closure)` 方法进行配置
    |
    */
    'content' => "【Yooul】Your Yooul security code is %s. You are trying to change the login password. Please keep your account information in a safe place.",

    'forget_password' => "【Yooul】Your Yooul security code is %s. You are trying to change the login password. Please keep your account information in a safe place.",

    'update_phone' => "【Yooul】Your Yooul security code is %s. You are trying to change the phone. Please keep your account information in a safe place.",

    'sign_in' => "【Helloo】Your Helloo security code is %s. Yours is logging in Helloo!. Please keep your account information in a safe place.",

    /*
    |--------------------------------------------------------------------------
    | 验证码短信模版
    |--------------------------------------------------------------------------
    |
    | 每项数据的值可以为以下三种之一:
    |
    | - 字符串/数字
    |   如: 'YunTongXun' => '短信模版id'
    |
    | - 数组
    |   如: 'Alidayu' => ['短信模版id', '语音模版id'],
    |
    | - 匿名函数
    |   如: 'YunTongXun' => function ($input, $type) {
    |           return $input['isRegister'] ? 'registerTempId' : 'commonId';
    |       }
    |
    | 如需缓存配置，则需使用 `Toplan\Sms\SmsManger::closure($closure)` 方法对匿名函数进行配置
    |
    */
    'templates' => [
        'Aliyun' => env('SMS_TEMPLATE_ID' , ''),
    ],
    'other_templates' => [
        'Aliyun' => env('SMS_UPDATE_PHONE_TEMPLATE_ID' , 'SMS_183445108'),
    ],
    'sign_in_templates' => [
        'Aliyun' => env('SMS_SIGN_UP_TEMPLATE_ID' , 'SMS_205431924'),
    ],

    'sms_templates'=>[
      'yunxin'=>[
          'sign_in'=>env('YUN_XIN_SMS_SIGN_UP_TEMPLATE_ID' , '14881706'),
          'forget_password'=>env('YUN_XIN_SMS_FORGET_PASSWORD_TEMPLATE_ID' , '14872716'),
      ],
      'aliyun'=>[
          'sign_in'=>env('ALIYUN_SMS_SIGN_UP_TEMPLATE_ID' , 'SMS_205431924'),
          'forget_password'=>env('ALIYUN_SMS_FORGET_PASSWORD_TEMPLATE_ID' , 'SMS_205437803'),
      ]
    ],
    'sign_name'=>[
      'yunxin'=>  env('YUN_XIN_SIGN_NAME' , 'Helloo'),
      'aliyun'=>  env('ALIYUN_SIGN_NAME' , 'Helloo'),
    ],
    /*
    |--------------------------------------------------------------------------
    | 模版数据管理
    |--------------------------------------------------------------------------
    |
    | 每项数据的值可以为以下两种之一:
    |
    | - 基本数据类型
    |   如: 'minutes' => 5
    |
    | - 匿名函数（如果该函数不返回任何值，即表示不使用该项数据）
    |   如: 'serialNumber' => function ($code, $minutes, $input, $type) {
    |           return $input['serialNumber'];
    |       }
    |   如: 'hello' => function ($code, $minutes, $input, $type) {
    |           //不返回任何值，那么hello将会从模版数据中移除 :)
    |       }
    |
    | 如需缓存配置，则需使用 `Toplan\Sms\SmsManger::closure($closure)` 方法对匿名函数进行配置
    |
    */
    'data' => [
        'code' => '',
        'minutes' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | 存储系统配置
    |--------------------------------------------------------------------------
    |
    | driver:
    | 存储方式,是一个实现了'Toplan\Sms\Storage'接口的类的类名,
    | 内置可选的值有'Toplan\Sms\SessionStorage'和'Toplan\Sms\CacheStorage',
    | 如果不填写driver,那么系统会自动根据内置路由的属性(route)中middleware的配置值选择存储器driver:
    | - 如果中间件含有'web',会选择使用'Toplan\Sms\SessionStorage'
    | - 如果中间件含有'api',会选择使用'Toplan\Sms\CacheStorage'
    |
    | prefix:
    | 存储key的prefix
    |
    | 内置driver的个性化配置:
    | - 在laravel项目的'config/session.php'文件中可以对'Toplan\Sms\SessionStorage'进行更多个性化设置
    | - 在laravel项目的'config/cache.php'文件中可以对'Toplan\Sms\CacheStorage'进行更多个性化设置
    |
    */
    'storage' => [
        'driver' => '',
        'prefix' => 'yooul_sms',
    ],

    /*
    |--------------------------------------------------------------------------
    | 是否数据库记录发送日志
    |--------------------------------------------------------------------------
    |
    | 若需开启此功能,需要先生成一个内置的'laravel_sms'表
    | 运行'php artisan migrate'命令可以自动生成
    |
    */
    'dbLogs' => false,

    /*
    |--------------------------------------------------------------------------
    | 队列任务
    |--------------------------------------------------------------------------
    |
    */
    'queueJob' => 'Toplan\Sms\SendReminderSms',

    /*
    |--------------------------------------------------------------------------
    | 验证码模块提示信息
    |--------------------------------------------------------------------------
    |
    */
    'notifies' => [
        // 频繁请求无效的提示
        'request_invalid' => '请求无效，请在%s秒后重试',

        // 验证码短信发送失败的提示
        'sms_sent_failure' => '短信验证码发送失败，请稍后重试',

        // 语音验证码发送发送成功的提示
        'voice_sent_failure' => '语音验证码请求失败，请稍后重试',

        // 验证码短信发送成功的提示
        'sms_sent_success' => '短信验证码发送成功，请注意查收',

        // 语音验证码发送发送成功的提示
        'voice_sent_success' => '语音验证码发送成功，请注意接听',
    ],
];
