<?php


namespace App\Providers;

use App\Custom\EasySms\EasySms;
use Illuminate\Support\ServiceProvider;
use App\Custom\EasySms\Gateways\AwsGateway;
use Overtrue\EasySms\Strategies\OrderStrategy;
use App\Custom\EasySms\Gateways\AliyunCustomGateway;
use App\Custom\EasySms\Gateways\YunxinCustomGateway;
use App\Custom\EasySms\Gateways\AliyunCNCustomGateway;
use App\Custom\EasySms\Gateways\YuntongxunCustomGateway;

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
            })->extend('aws', function($config) {
                return new AwsGateway($config);
            })->extend('aliYunCNCustom', function($config) {
                return new AliyunCNCustomGateway($config);
            })->extend('yunTongXunCustom', function($config) {
                return new YuntongxunCustomGateway($config);
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
                'aliYunCNCustom' =>  config('easy-sms.agents.aliyun'),
                'yunxin' => config('easy-sms.agents.yunxin'),
                'yunXinCustom' => config('easy-sms.agents.yunxin'),
                'aws' => config('easy-sms.agents.aws'),
                'yunTongXunCustom' => config('easy-sms.agents.yuntongxun'),
            ],
        ];
    }

    public function provides()
    {
        return ['easy-sms'];
    }

}