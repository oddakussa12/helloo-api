<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;

use App\Jobs\Device;
use Carbon\Carbon;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreDeviceRequest;

class DeviceController extends BaseController
{

    public function index()
    {
        
    }

    public function store(StoreDeviceRequest $request)
    {
        $deviceFields = $request->all();
        $device = new Device($deviceFields);
        $this->dispatch($device->onQueue('registered_plant_tsm'));
        return $this->response->created();
    }

    /**
     * @param StoreDeviceRequest $request
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
