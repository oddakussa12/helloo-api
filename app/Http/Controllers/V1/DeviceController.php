<?php

namespace App\Http\Controllers\V1;

use App\Jobs\Device;
use App\Models\Es;
use App\Repositories\Contracts\DeviceRepository;
use App\Services\BaseEsService;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreDeviceRequest;

class DeviceController extends BaseController
{
    /**
     * @var EsClient
     */
    private $esClient;
    /**
     * @var string
     */
    private $mIndex;

    /**
     * @var DeviceRepository
     */

    public function __construct()
    {
        //$this->esClient = new BaseEsService(config('scout.elasticsearch.index'));
        //$this->esClient = $clientBuilder;
        //$this->mIndex = 'device';
    }

    public function test()
    {

        exit;
        set_time_limit(0);

        $countSql = "SELECT count(1) num
FROM f_posts_translations t
inner join f_posts p on p.post_id = t.post_id
where p.post_created_at > '2020-01-01'
ORDER BY t.post_id desc;";


        $countResult = DB::select($countSql);

        $count  = $countResult[0]->num;
        $limit  = 1000;
        $page   = intval(ceil($count/$limit));
        sleep(1);
        dump($count, $limit, $page);

        for ($i=0;$i<$page;$i++) {
            $offset = $limit*$i;
            $sql = "
        SELECT p.post_id,p.post_uuid,p.user_id,p.post_category_id,p.post_media,p.post_content_default_locale,p.post_type,
/*MAX(CASE t.`post_locale` WHEN 'en' THEN t.`post_content` ELSE '' END) as 'en',
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
MAX(CASE t.`post_locale` WHEN 'ru' THEN t.`post_content` ELSE '' END) as 'ru',*/
t.post_locale, t.post_content,
p.post_created_at as create_at
FROM f_posts_translations t
inner join f_posts p on p.post_id = t.post_id
where p.post_created_at > '2020-01-01'
ORDER BY t.post_id desc
        ";

            $sql .= "limit $offset, $limit";

            $result = DB::select($sql);
            $result = array_map('get_object_vars', $result);
            echo '-----------'. env('ELASTICSEARCH_POST_INDEX');

            $data = (new BaseEsService('post'))->batchCreate($result);
            if ($data==null) {
                $data = (new BaseEsService('post'))->batchCreate($result);
            }
            dump($data);
            flush();
            ob_flush();
        }


        dump(123);
        exit;
        (new Es('post'))->generateDoc($result);

        exit;
         Report::where('reportable_id', $post->getKey())
            ->where('reportable_type', $post->getMorphClass())
            ->select(\DB::raw('DISTINCT user_id'))->orderBy('id' , 'DESC')->limit(intval(config('common.report_post_num')+config('common.report_limit_num')))->pluck('user_id')->all();
        $reportNum = count($relation);

        $result = DB::table(DB::raw($sql));

        return ($result);
        exit;
        $device = \App\Models\Device::select('device_type', 'device_registration_id')->limit(1)->first();

        $device->device_type =rand(1,2);
        $device->device_registration_id =time();
        $device->user_id = rand(3300, 3500);
        dump($device);
        $device->save();

    }
    public function index()
    {



        $client = new Client(config('scout.elasticsearch'));

#单条插入
        $params = [
            'index' => 'device',
            'type'  => '',
            'body' => [
                'device_type' => rand(1,2),
                'device_registration_id' => time(),
            ]
        ];
// $params['id'] = 'w1231313';
        return $client->index($params);


        $this->esClient->index($params);

       return ($params);

        exit;
        $device = \App\Models\Device::select('id','device_type', 'device_registration_id')->limit(1)->first();
        $device->device_type =rand(1,2);
        $device->device_registration_id =time();
        dump($device);
        $device->create();
        //return $this->deviceRepository->store($params);
    }

    public function store(StoreDeviceRequest $request)
    {
        $deviceFields = $request->all();
        $device = new Device($deviceFields);
        $this->dispatch($device->onQueue('registered_plant'));
        return $this->response->created();
    }
    
}
