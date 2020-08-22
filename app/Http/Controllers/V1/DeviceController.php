<?php

namespace App\Http\Controllers\V1;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Jobs\Device;
use App\Models\Es;
use App\Repositories\Contracts\DeviceRepository;
use Elasticsearch\Client;
use Carbon\Carbon;
use Dingo\Api\Http\Response;
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
    private $postIndex;

    /**
     * @var DeviceRepository
     */
    public function __construct()
    {
        $this->postIndex = config('scout.elasticsearch.post');
    }

    public function test()
    {



       /* exit;
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
        $device->save();*/

    }
    public function index()
    {
        /*$device = \App\Models\Device::select('id','device_type', 'device_registration_id')->limit(1)->first();
        $device->device_type =rand(1,2);
        $device->device_registration_id =time();
        dump($device);
        $device->create();*/
        //return $this->deviceRepository->store($params);
    }

    public function store(StoreDeviceRequest $request)
    {
        $deviceFields = $request->all();
        $device = new Device($deviceFields);
        $this->dispatch($device->onQueue('registered_plant'));
        return $this->response->created();
    }

    /**
     * @param Request $request
     * @return Response
     * 修改设备语言
     */
    public function update(Request $request)
    {
        $language = $request->input('deviceLanguage');
        $userId   = auth()->user()->user_id;
        if (!empty($language) && !empty($userId)) {
            $data = ['device_language'=>$language, 'device_updated_at'=>Carbon::now()];
            \DB::table('devices')->where('user_id', $userId)->update($data);
        }
        return $this->response->accepted();
    }
    
}
