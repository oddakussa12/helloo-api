<?php

namespace App\Http\Controllers\V1;

use App\Jobs\Device;
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
        $this->dispatch($device->onQueue('registered_plant'));
        return $this->response->created();
    }
    
}
