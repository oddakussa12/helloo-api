<?php

namespace App\Console\Commands;

use App\Http\Controllers\V1\DeviceController;
use App\Models\Es;
use App\Services\EsClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ESinit extends Command
{

    //使用什么命令启动脚本
    protected $signature = 'es:init {param}';
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
            if ($param == 'topic') {
                $this->topic_index();
                $this->info('============create topic index success============');

            } else if ($param == 'user') {
                $this->user_index();
                $this->info('============create test index success============');

            } else if ($param == 'post') {
                $this->post_index();
                $this->info('============create postDataInit success============');

            } else if ($param == 'postdata') {
                $this->postDataInit();
                $this->info('============create postDataInit success============');
            } else if ($param == 'topicdata') {
                $this->topicDataInit();
                $this->info('============create topicDataInit success============');
            } else if ($param == 'userdata') {
                $this->userDataInit();
                $this->info('============create userDataInit success============');
            }


        }catch (\Exception $e) {
            \Log::info('test.log', [$e->getCode(), $e->getMessage()]);
        }

    }

    /**
     * post 索引
     */
    public function post_index()
    {
        $this->indices($this->post_init());
    }
    /**
     * topic 索引
     */
    public function topic_index()
    {
        $this->indices($this->topic_init());
    }

    /**
     * topic 索引
     */
    public function user_index()
    {
        $this->indices($this->user_init());
    }

    public function indices($index)
    {
        $indices = [$index];
        foreach ($indices as $index) {
            try {
                dump(json_encode($index));
                (new EsClient())->indices()->create($index);
                echo 'create index: ' . $index['index'] . PHP_EOL;
            } catch (\Exception $e) {
                dump(json_encode($index));
                echo "Exception::: code: ". $e->getCode(). ' message: '. $e->getMessage() . PHP_EOL;
            }
        }
    }

    private function post_init()
    {
        return array_merge_recursive([
            'index' => config('scout.elasticsearch.post'),
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
                        'post_event_country_id' => [
                            'type' => 'long',
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
                            'type'      => 'text',
                            "analyzer"  => "icu_analyzer",
                            'fields'    => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256],
                                "suggest" => ["type" => "completion", "analyzer" => "icu_analyzer",'preserve_separators'=>false,"max_input_length"=>50]
                            ],
                        ],
                        'post_create_at' => [
                            'type' => 'text',
                        ],
                        'post_is_delete' => [
                            'type' => 'text'
                        ]
                    ]
                ]
            ]
        ], $this->setting);
    }

    private function topic_init()
    {
        return array_merge_recursive([
            'index' => config('scout.elasticsearch.topic'),
            'body'  => [
                'mappings' => [
                    'properties' => [
                        'topic_content' => [
                            'type'      => 'text',
                            "analyzer"  => "icu_analyzer",
                            'fields'    => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256],
                                "suggest" => ["type" => "completion", "analyzer" => "icu_analyzer",'preserve_separators'=>false,"max_input_length"=>50]
                            ],
                        ],
                        'topic_created_at' => [
                            'type' => 'text',
                        ]
                    ]
                ]
            ]
        ], $this->setting);
    }

    private function user_init()
    {
        return array_merge_recursive([
            'index' => config('scout.elasticsearch.user'),
            'body'  => [
                'mappings' => [
                    'properties' => [
                        'user_id' => [
                            'type' => 'long',
                        ],
                        'user_name' => [
                            'type'      => 'text',
                            "analyzer"  => "icu_analyzer",
                            'fields'    => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256],
                                "suggest" => ["type" => "completion", "analyzer" => "icu_analyzer",'preserve_separators'=>false,"max_input_length"=>50]
                            ],
                        ],
                        'user_nick_name' => [
                            'type'      => 'text',
                            "analyzer"  => "icu_analyzer",
                            'fields'    => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256],
                                "suggest" => ["type" => "completion", "analyzer" => "icu_analyzer",'preserve_separators'=>false,"max_input_length"=>50]
                            ],
                        ],
                        'user_avatar' => [
                            'type' => 'text',
                        ],
                        'user_country_id' => [
                            'type' => 'text',
                        ],
                        'user_gender' => [
                            'type' => 'text',
                        ],
                        'user_about' => [
                            'type' => 'text',
                        ],
                        'user_level' => [
                            'type' => 'text',
                        ],
                        'user_birthday' => [
                            'type' => 'text',
                        ]

                    ]
                ]
            ]
        ], $this->setting);
    }

    /**
     * post数据导入
     */
    public function postDataInit()
    {
        $where = " WHERE p.post_id<=675441 and p.post_id>670000";
        //$where = " WHERE p.post_id<=670000 and p.post_id>600000";
        //$where = " WHERE p.post_id<=600000 and p.post_id>550000";
        //$where = " WHERE p.post_id<=550000 and p.post_id>500000";
        //$where = " WHERE p.post_id<=500000 and p.post_id>450000";
        //$where = " WHERE p.post_id<=450000 and p.post_id>400000";
        //$where = " WHERE p.post_id<=400000 and p.post_id>350000";
        //$where = " WHERE p.post_id<=350000 and p.post_id>300000";
        //$where = " WHERE p.post_id<=300000 and p.post_id>250000";
        //$where = " WHERE p.post_id<=250000 and p.post_id>200000";
        //$where = " WHERE p.post_id<=200000 and p.post_id>150000";
        //$where = " WHERE p.post_id<=150000 and p.post_id>100000";
        //$where = " WHERE p.post_id<=100000 and p.post_id>50000";
        //$where = " WHERE p.post_id<=50000";

        $countSql = "SELECT count(1) num
                    FROM f_posts_translations t
                    inner join f_posts p on p.post_id = t.post_id
                    $where
                    ORDER BY t.post_id desc";

        $limitSql = "SELECT p.post_id,p.post_uuid,p.user_id,p.post_category_id,p.post_media,p.post_content_default_locale,p.post_type,
                    p.post_event_country_id, t.post_locale, t.post_content,
                    p.post_created_at as create_at
                    FROM f_posts_translations t
                    inner join f_posts p on p.post_id = t.post_id
                    $where
                    ORDER BY t.post_id desc ";

        $this->dataInit($countSql, $limitSql, config('scout.elasticsearch.post'));
    }


    public function userDataInit()
    {
        $where    = "where user_id > 570000 ";
        $countSql = "SELECT count(1) num FROM f_users $where";
        $limitSql = "SELECT user_id,user_name, user_nick_name, user_avatar,user_country_id,user_gender,user_about,user_level,user_birthday FROM f_users $where order by user_id asc ";

        $this->dataInit($countSql, $limitSql, config('scout.elasticsearch.user'));
    }

    public function topicDataInit()
    {
        $countSql = "SELECT count(1) num FROM f_topics";
        $limitSql = "SELECT topic_content, topic_created_at FROM f_topics ";

        $this->dataInit($countSql, $limitSql, config('scout.elasticsearch.topic'));
    }

    public function dataInit($countSql, $limitSql, $index)
    {
        dump('防止误操作，停止执行导入操作。');
        return;

        dump('开始插入数据');
        set_time_limit(0);

        $countResult = DB::select($countSql);

        $count  = $countResult[0]->num;
        $limit  = 2000;
        $page   = intval(ceil($count/$limit));
        sleep(1);
        dump($count, $limit, $page, "for start:");

        $total = [];
        for ($j=1;$j<=120;$j++){
            $total[] = $j*100000;
        }
        for ($i=0;$i<=$page;$i++) {
            $offset = $limit*$i;

            $limitSql2 = $limitSql. " limit $offset, $limit";

            dump($limitSql2);
            $result = DB::select($limitSql2);
            $result = array_map('get_object_vars', $result);

            dump('foreach:: '. $offset);
            sleep(1);

            if ($result) {
                $data = (new Es($index))->batchCreate($result);
                if ($data==null) {
                    (new Es($index))->batchCreate($result);
                }
            }
            if (in_array($offset, $total)) {
                dump("休息10秒...");
                sleep(10);
            }
        }
        dump('插入数据完成');
        return;
    }
}
