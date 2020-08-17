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

        $sql = "
        SELECT post_id,
MAX(CASE `post_locale` WHEN 'en' THEN post_content ELSE '' END) as 'en',
MAX(CASE `post_locale` WHEN 'id' THEN post_content ELSE '' END) as 'hindi',
MAX(CASE `post_locale` WHEN 'zh-CN' THEN post_content ELSE '' END) as 'zhCN',
MAX(CASE `post_locale` WHEN 'ar' THEN post_content ELSE '' END) as 'ar',
MAX(CASE `post_locale` WHEN 'hi' THEN post_content ELSE '' END) as 'hi',
MAX(CASE `post_locale` WHEN 'ko' THEN post_content ELSE '' END) as 'ko',
MAX(CASE `post_locale` WHEN 'ja' THEN post_content ELSE '' END) as 'ja',
MAX(CASE `post_locale` WHEN 'es' THEN post_content ELSE '' END) as 'es',
MAX(CASE `post_locale` WHEN 'zh-TW' THEN post_content ELSE '' END) as 'zhTW',
MAX(CASE `post_locale` WHEN 'zh-HK' THEN post_content ELSE '' END) as 'zhHK',
MAX(CASE `post_locale` WHEN 'vi' THEN post_content ELSE '' END) as 'vi',
MAX(CASE `post_locale` WHEN 'th' THEN post_content ELSE '' END) as 'th',
MAX(CASE `post_locale` WHEN 'fr' THEN post_content ELSE '' END) as 'fr',
MAX(CASE `post_locale` WHEN 'de' THEN post_content ELSE '' END) as 'de',
MAX(CASE `post_locale` WHEN 'ru' THEN post_content ELSE '' END) as 'ru',
post_translation_created_at as create_at
FROM f_posts_translations 
where post_translation_created_at > '2020-01-01'
GROUP BY post_id 
ORDER BY post_id desc;
        ";


        $result = DB::select($sql);
        $result = array_map('get_object_vars', $result)[0];
        //(new BaseEsService('post'))->create($result);


        (new BaseEsService('post'))->create($result);
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
