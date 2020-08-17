<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\Es;
use App\Models\OperationLog;
use App\Services\EsClient;
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
     * @var array[]
     */
    private $setting;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->setting = [
            'body' => [
                'settings' => [
                    'number_of_shards' => 3,
                    'number_of_replicas' => 0
                ],
                'mappings' => [
                    '_source' => [
                        'enabled' => true
                    ],
//                    'properties' => [
//                        '@timestamp' => [
//                            'format' => 'strict_date_optional_time||epoch_millis',
//                            'type' => 'date'
//                        ]
//                    ]
                ]
            ]
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        try{
            $this->indices();
            $this->info('============create index success============');


            //创建template
            /*
            $this->info('============create template success============');
            $this->createIndex($client);
            $this->info('============create index success============');
            $this->createData($client);
            $this->info('============create data success============');*/

        }catch (\Exception $e){
            \Log::info('test.log', [$e->getCode(), $e->getMessage()]);
        }

    }

    public function indices()
    {
        $indices = [
            $this->device_init()
            //$this->index_init(),
        ];
        foreach ($indices as $index) {
            try {
                (new EsClient())->indices()->create($index);
                echo 'create index: ' . $index['index'] . PHP_EOL;
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
        }
    }

    /**
     * @var OperationLog
     * @return array
     */
    private function index_init()
    {
        return array_merge_recursive([
            'index' => config('scout.elasticsearch.index'),
            'body' => [
                'mappings' => [
                    'properties' => [
                        'create_at' => [
                            'type' => 'long'
                        ],
                        'update_at' => [
                            'type' => 'long'
                        ],
                        'is_delete' => [
                            'type' => 'long'
                        ],
                        'name' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                        'title' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                        'description' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                        'time_start' => [
                            'type' => 'long'
                        ],
                        'time_end' => [
                            'type' => 'long'
                        ]
                    ]
                ]
            ]
        ], $this->setting);
    }

    private function device_init()
    {
        return array_merge_recursive([
            //'index' => 'device'.rand(1,100),
            'index' => config('scout.elasticsearch.index'),
            'body' => [
                'mappings' => [
                    'properties' => [

                        'user_id' => [
                            'type' => 'long'
                        ],
                        'device_type' => [
                            'type' => 'long',
                        ],
                        'device_registration_id' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                        /*'device_language' => [
                            'type' => 'text',
                            'analyzer' => 'ik_max_word'
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],

                        'device_country' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                        'device_phone_model' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],*/
                    ]
                ]
            ]
        ], $this->setting);
    }
}
