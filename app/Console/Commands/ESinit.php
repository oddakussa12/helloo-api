<?php

namespace App\Console\Commands;

use App\Http\Controllers\V1\DeviceController;
use App\Services\EsClient;
use Illuminate\Console\Command;

class ESinit extends Command
{

    //使用什么命令启动脚本
    protected $signature = 'es:init {param?}';
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
        $param = $this->argument('param');

        try {
            if ($param == 'suggest') {
                $this->suggest_index();
                $this->info('============create suggest index success============');

            } else if ($param == 'post') {
                $this->postDataInit();
                $this->info('============create postDataInit success============');
            } else {
                $this->indices();
                $this->info('============create index success============');
            }





            //创建template
            /*
            $this->info('============create template success============');
            $this->createIndex($client);
            $this->info('============create index success============');
            $this->createData($client);
            $this->info('============create data success============');*/

        }catch (\Exception $e) {
            \Log::info('test.log', [$e->getCode(), $e->getMessage()]);
        }

    }
    public function suggest_index()
    {
            try {
                $index = $this->suggest_init();
                (new EsClient())->indices()->create($index);
                echo 'create index: ' . $index['index'] . PHP_EOL;
            } catch (\Exception $e) {
                echo "Exception::: code: ". $e->getCode(). ' message: '. $e->getMessage() . PHP_EOL;
            }
    }

    public function indices()
    {
        $indices = [
            $this->post_init(),
        ];
        foreach ($indices as $index) {
             try {
                 dump(json_encode($index));
                 (new EsClient())->indices()->create($index);
                 echo 'create index: ' . $index['index'] . PHP_EOL;
             } catch (\Exception $e) {

                 dump($e);
                 echo "Exception::: code: ". $e->getCode(). ' message: '. $e->getMessage() . PHP_EOL;
             }
        }
    }

    /**
     * @return array
     * 联想词
     */
    private function suggest_init() {
        $json = '{
          "body": {
          "settings": {
            "analysis": {
              "analyzer": {
                "prefix_pinyin_analyzer": {
                  "tokenizer": "standard",
                  "filter": [
                    "lowercase",
                    "prefix_pinyin"
                  ]
                },
                "full_pinyin_analyzer": {
                  "tokenizer": "standard",
                  "filter": [
                    "lowercase",
                    "full_pinyin"
                  ]
                }
              },
              "filter": {
                "_pattern": {
                  "type": "pattern_capture",
                  "preserve_original": true,
                  "patterns": [
                    "([0-9])",
                    "([a-z])"
                  ]
                },
                "prefix_pinyin": {
                  "type": "pinyin",
                  "keep_first_letter": true,
                  "keep_full_pinyin": false,
                  "none_chinese_pinyin_tokenize": false,
                  "keep_original": false
                },
                "full_pinyin": {
                  "type": "pinyin",
                  "keep_first_letter": false,
                  "keep_full_pinyin": true,
                  "keep_original": false,
                  "keep_none_chinese_in_first_letter": false
                }
              }
            }
          },
          "mappings": {
            "suggest": {
              "properties": {
                "id": {
                  "type": "string"
                },
                "suggestText": {
                  "type": "completion",
                  "analyzer": "icu_analyzer",
                  "payloads": true,
                  "preserve_separators": false,
                  "preserve_position_increments": true,
                  "max_input_length": 50
                },
                "prefix_pinyin": {
                  "type": "completion",
                  "analyzer": "prefix_pinyin_analyzer",
                  "search_analyzer": "standard",
                  "preserve_separators": false
                },
                "full_pinyin": {
                  "type": "completion",
                  "analyzer": "full_pinyin_analyzer",
                  "search_analyzer": "full_pinyin_analyzer",
                  "preserve_separators": false
                }
              }
            }
          }
        }
        }';

        return array_merge_recursive(json_decode($json, true), $this->setting);
    }

    private function post_init()
    {
        return array_merge_recursive([
            'index' => env('ELASTICSEARCH_POST_INDEX'),
            'body'  => [
                'mappings' => [
                    'properties' => [
                        'post_id' => [
                            'type' => 'long',
                        ],
                        'post_uuid' => [
                            'type' => 'text',
                        ],
                        'user_id' => [
                            'type' => 'long',
                        ],
                        'post_category_id' => [
                            'type' => 'text',
                        ],
                        'post_media' => [
                            'type' => 'text',
                        ],
                        'post_content_default_locale' => [
                            'type' => 'text',
                        ],
                        'post_type' => [
                            'type' => 'text',
                        ],
                        'post_locale' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                        'post_content' => [
                            'type'     => 'text',
                            'fields'   => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]],
                            "suggest"  => ["type"=> "completion", "analyzer"=> "icu_analyzer"]
                        ],
                        'create_at' => [
                            'type' => 'text',
                        ]

                    ]
                ]
            ]
        ], $this->setting);
    }

    private function test_init()
    {
        return array_merge_recursive([
            'index' => env('ELASTICSEARCH_POST_INDEX'),
            'body'  => [
                'mappings' => [
                    'properties' => [
                        'post_content' => [
                            'type'     => 'text',
                            'fields'   => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]],
                            "suggest"  => ["type"=> "completion", "analyzer"=> "icu_analyzer"]
                        ]
                    ]
                ]
            ]
        ], $this->setting);
    }

    public function postDataInit()
    {
        dump('开始插入数据');
        (new DeviceController())->test();
        dump('插入数据完成');
    }
}
