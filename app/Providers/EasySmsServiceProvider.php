<?php


namespace App\Providers;

use Overtrue\EasySms\EasySms;
use Illuminate\Support\ServiceProvider;
use Overtrue\EasySms\Strategies\OrderStrategy;

class EasySmsServiceProvider extends ServiceProvider
{
    /**
     * 注册服务.
     * @https://learnku.com/articles/6189/laravel-service-provider-detailed-concept
     * @return void
     */
    public function register()
    {
        $config = $this->getConfig();
        $this->app->singleton('easy-sms', function ($app) use ($config){
            return new EasySms($config);
        });
    }

    public function getConfig()
    {
        return [
            // HTTP 请求的超时时间（秒）
            'timeout' => 5.0,

            // 默认发送配置
            'default' => [
                // 网关调用策略，默认：顺序调用
                'strategy' => OrderStrategy::class,

                // 默认可用的发送网关
                'gateways' => [
                    'yunxin'
                ],
            ],
            // 可用的网关配置
            'gateways' => [
                'errorlog' => [
                    'file' => 'storage/log/laravel.log',
                ],
                'yunpian' => [
                    'api_key' => '824f0ff2f71cab52936axxxxxxxxxx',
                ],
                'aliyun' => [
                    'access_key_id' => '',
                    'access_key_secret' => '',
                    'sign_name' => '',
                ],
                'yunxin' => [
                    'app_key' => '4422695a31e00df8bcdb669eb2dbb398',
                    'app_secret' => 'ef0db8065542',
                    'code_length' => 4, // 随机验证码长度，范围 4～10，默认为 4
                    'need_up' => false, // 是否需要支持短信上行
                ],
            ],
        ];
    }

    public function provides()
    {
        return ['easy-sms'];
    }

}