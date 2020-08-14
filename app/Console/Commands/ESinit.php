<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ESinit extends Command
{

    //使用什么命令启动脚本
    protected $signature = 'es:init';
    //描述
    protected $description = 'init laravel es for post';
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try{
//创建template
            $client = new \GuzzleHttp\Client(); //这里的Clinet()是你vendor下的GuzzleHttp下的Client文件
            $this->createTemplate($client);
            $this->info('============create template success============');
            $this->createIndex($client);
            $this->info('============create index success============');
        }catch (\Exception $e){
            ownLogs('test.log', $e->getMessage());
        }

    }
    /**
     * 创建模板 see https://www.elastic.co/guide/en/elasticsearch/reference/current/dynamic-templates.html
     * @param Client $client
     */
    private function createTemplate($client)
    {
        $url = config('scout.elasticsearch.hosts')[0] . '/_template/template_1';
// $client->delete($url);
        $client->put($url, [

            'json' => [
                'index_patterns' => [config('scout.elasticsearch.index').'*'],
                'settings' => [
                    'number_of_shards' => 1,
                ],
                'mappings' => [
                    '_source' => [
                        'enabled' => true
                    ],
                    'properties' => [
                        'mapping' => [ // 字段的处理方式
                            'type' => 'keyword', // 字段类型限定为 string
                            'fields' => [
                                'raw' => [
                                    'type' => 'keyword',
                                    'ignore_above' => 256, // 字段是索引时忽略长度超过定义值的字段。
                                ]
                            ],
                        ],
                    ],

                ],
            ],
        ]);
    }

    /**
     * 创建索引
     * @param Client $client
     */
    private function createIndex($client)
    {
        $url = config('scout.elasticsearch.hosts')[0] . '/' . config('scout.elasticsearch.index');
        // $client->delete($url);
        $client->put($url, [
            'json' => [
                'settings' => [
                    'refresh_interval' => '5s',
                    'number_of_shards' => 1, // 分片为
                    'number_of_replicas' => 0, // 副本数
                ],
            ],
        ]);
    }
}
