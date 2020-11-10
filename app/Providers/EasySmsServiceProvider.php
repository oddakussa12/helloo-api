<?php


namespace App\Providers;

use Overtrue\EasySms\EasySms;
use Illuminate\Support\ServiceProvider;
use App\Custom\EasySms\Gateways\AliyunCustomGateway;
use App\Custom\EasySms\Gateways\YunxinCustomGateway;
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
            $easySms =  new EasySms($config);
            return $easySms->extend('aliYunCustom', function($config) {
                return new AliyunCustomGateway($config);
            })->extend('yunXinCustom', function($config) {
                return new YunxinCustomGateway($config);
            });
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
                    'yunxin','aliyun'
                ],
            ],
            // 可用的网关配置
            'gateways' => [
                'errorlog' => [
                    'file' => 'storage/log/laravel.log',
                ],
                'aliyun' =>  config('easy-sms.agents.aliyun'),
                'aliYunCustom' =>  config('easy-sms.agents.aliyun'),
                'yunxin' => config('easy-sms.agents.yunxin'),
                'yunXinCustom' => config('easy-sms.agents.yunxin'),
            ],
        ];
    }

    public function provides()
    {
        return ['easy-sms'];
    }

}