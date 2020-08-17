<?php

namespace App\Http\Controllers\V1;

use App\Jobs\Device;
use App\Repositories\Contracts\DeviceRepository;
use App\Repositories\Contracts\PostCommentRepository;
use App\Services\BaseEsService;
use Dingo\Api\Routing\Helpers;
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

    public function __construct(ClientBuilder $clientBuilder)
    {
        //$this->esClient = new BaseEsService(config('scout.elasticsearch.index'));
        //$this->esClient = $clientBuilder;
        //$this->mIndex = 'device';
    }

    public function test()
    {
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
