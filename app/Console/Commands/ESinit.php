<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;

class ESinit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        //

        //创建template
        $client = new Client(); //这里的Clinet()是你vendor下的GuzzleHttp下的Client文件
        $url = config('scout.elasticsearch.hosts')[0].'/inssa';   //这里写logstash配置中index参数
        $client->delete($url);//确定没有这个url

        /*
         * 这个模板作用于我要做用的索引
         * */
        $param = [
            'json'=>[
                /*
                 * 这句是取在scout.php（scout是驱动）里我们配置好elasticsearch引擎的
                 * index项。
                 * PS：其实都是取数组项，scout本身就是return一个数组，
                 * scout.elasticsearch.index就是取
                 * scout[elasticsearch][index]
                 * */
                'template'=>config('scout.elasticsearch.index'),
                'mappings'=>[
                    '_default_'=>[
                        'dynamic_templates'=>[
                            [
                                'string'=>[
                                    'match_mapping_type'=>'string',//传进来的是string
                                    'mapping'=>[
                                        'type'=>'text',//把传进来的string按text（文本）处理
                                        'analyzer'=>'ik_smart',//用ik_smart进行解析（ik是专门解析中的插件）
                                        'fields'=>[
                                            'title'=>[
                                                'type'=>'text'
                                            ],
                                            'keyword'=>[
                                                'type'=>'keyword'
                                            ],
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
        ];
        $client->put($url,$param);

        $this->info('============create template success============');

//创建index
        $url = config('scout.elasticsearch.hosts')[0].'/'.config('scout.elasticsearch.index');
//$client->delete($url);

        $param = [
            'json'=>[
                'settings'=>[
                    'refresh_interval'=>'5s',
                    'number_of_shards'=>1,
                    'number_of_replicas'=>0,
                ],

                'mappings'=>[
                    '_default_'=>[
                        '_all'=>[
                            'enabled'=>false
                        ]
                    ]
                ]
            ]
        ];

        $client->put($url,$param);
        $this->info('============create index success============');

    }
}
