<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\Es;
use App\Models\OperationLog;
use App\Services\BaseEsService;
use App\Services\EsClient;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
            $this->postDataInit();
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
            $this->post_init(),
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

    private function post_init()
    {
        return array_merge_recursive([
            //'index' => 'device'.rand(1,100),
            'index' => 'post',
            'body'  => [
                'mappings' => [
                    'properties' => [
                        'user_id' => [
                            'type' => 'long',
                        ],
                        'post_id' => [
                            'type' => 'long',
                        ],
                        'post_uuid' => [
                            'type' => 'text',
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
                            'type' => 'tinyint',
                        ],
                        'create_at' => [
                            'type' => 'text',
                        ],
                        'en' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                        'hindi' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                        'device_registration_id' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                        'zhCN' => [
                            'type' => 'text',
                            'analyzer' => 'ik_max_word',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                        'ar' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                        'hi' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                        'ko' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                        'ja' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                        'es' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                        'zhTW' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                        'zhHK' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                        'vi' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                        'th' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                        'fr' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                        'de' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                        'ru' => [
                            'type' => 'text',
                            'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]
                        ],
                    ]
                ]
            ]
        ], $this->setting);
    }

    public function postDataInit()
    {
        $sql = "
        SELECT p.post_id,p.post_uuid,p.user_id,p.post_category_id,p.post_media,p.post_content_default_locale,p.post_type
MAX(CASE t.`post_locale` WHEN 'en' THEN t.`post_content` ELSE '' END) as 'en',
MAX(CASE t.`post_locale` WHEN 'id' THEN t.`post_content` ELSE '' END) as 'hindi',
MAX(CASE t.`post_locale` WHEN 'zh-CN' THEN t.`post_content` ELSE '' END) as 'zhCN',
MAX(CASE t.`post_locale` WHEN 'ar' THEN t.`post_content` ELSE '' END) as 'ar',
MAX(CASE t.`post_locale` WHEN 'hi' THEN t.`post_content` ELSE '' END) as 'hi',
MAX(CASE t.`post_locale` WHEN 'ko' THEN t.`post_content` ELSE '' END) as 'ko',
MAX(CASE t.`post_locale` WHEN 'ja' THEN t.`post_content` ELSE '' END) as 'ja',
MAX(CASE t.`post_locale` WHEN 'es' THEN t.`post_content` ELSE '' END) as 'es',
MAX(CASE t.`post_locale` WHEN 'zh-TW' THEN t.`post_content` ELSE '' END) as 'zhTW',
MAX(CASE t.`post_locale` WHEN 'zh-HK' THEN t.`post_content` ELSE '' END) as 'zhHK',
MAX(CASE t.`post_locale` WHEN 'vi' THEN t.`post_content` ELSE '' END) as 'vi',
MAX(CASE t.`post_locale` WHEN 'th' THEN t.`post_content` ELSE '' END) as 'th',
MAX(CASE t.`post_locale` WHEN 'fr' THEN t.`post_content` ELSE '' END) as 'fr',
MAX(CASE t.`post_locale` WHEN 'de' THEN t.`post_content` ELSE '' END) as 'de',
MAX(CASE t.`post_locale` WHEN 'ru' THEN t.`post_content` ELSE '' END) as 'ru',
p.post_created_at as create_at
FROM f_posts_translations t
inner join f_posts p on p.post_id = t.post_id
where p.post_created_at > '2020-01-01'
GROUP BY t.post_id 
ORDER BY t.post_id desc;
        ";

        $result = DB::select($sql);
        $result = array_map('get_object_vars', $result);
        (new BaseEsService('post'))->batchCreate($result);

    }
}
