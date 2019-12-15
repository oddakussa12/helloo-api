<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Device implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $deviceFields;

    public function __construct($deviceFields)
    {
        $this->deviceFields = $deviceFields;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $deviceFields = $this->deviceFields;
        $deviceId = isset($deviceFields['deviceToken'])?$deviceFields['deviceToken']:'';
        $appShortVersion = $deviceFields['appShortVersion'];
        $appVersion = $deviceFields['appVersion'];
        $appBundleIdentifier = $deviceFields['appBundleIdentifier'];
        $vendorUUID = $deviceFields['vendorUUID'];
        $systemName = $deviceFields['systemName'];
        $systemVersion = $deviceFields['systemVersion'];
        $phoneName = $deviceFields['phoneName'];
        $phoneModel = $deviceFields['phoneModel'];
        $localizedModel = $deviceFields['localizedModel'];
        $networkType = $deviceFields['networkType'];
        $carrierName = $deviceFields['carrierName'];
        $deviceType = $deviceFields['deviceType'];
        $device= DB::table('devices')->where('device_vendor_uuid' , $vendorUUID)->first();
        $dateTime = Carbon::now();
        if(empty($device))
        {
            $deviceData = array(
                'device_id' => $deviceId,
                'device_app_short_version' => $appShortVersion,
                'device_app_version' => $appVersion,
                'device_app_bundle_identifier' => $appBundleIdentifier,
                'device_vendor_uuid' => $vendorUUID,
                'device_system_name' => $systemName,
                'device_system_version' => $systemVersion,
                'device_phone_name' => $phoneName,
                'device_phone_model' => $phoneModel,
                'device_localized_model' => $localizedModel,
                'device_network_type' => $networkType,
                'device_carrier_name' => $carrierName,
                'device_type' => $deviceType,
                'device_created_at' => $dateTime,
                'device_updated_at' => $dateTime,
            );
            DB::table('devices')->insert($deviceData);
        }else{
            if($device->device_id!=$deviceId)
            {
                DB::table('devices')->where('device_vendor_uuid' , $vendorUUID)->update(array('device_id'=>$deviceId , 'device_updated_at' => $dateTime));
            }
        }
    }
}
