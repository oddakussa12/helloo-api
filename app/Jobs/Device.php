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

    public $type;

    public $userId;

    public function __construct($deviceFields , $type='init')
    {
        $this->deviceFields = $deviceFields;
        $this->type = $type;
        if(auth()->check())
        {
            $this->userId = auth()->id();
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $dateTime = Carbon::now();
        $type = $this->type;
        $deviceFields = $this->deviceFields;
        if($type=='signUpOrIn')
        {
            if(!empty($deviceFields['deviceToken'])&&!empty($deviceFields['vendorUUID']))
            {
                $userId = empty($this->userId)?0:$this->userId;
                $device= DB::table('devices')->where('device_vendor_uuid' , $deviceFields['vendorUUID'])->first();
                if(!empty($device))
                {
                    if($device->device_id!=$deviceFields['deviceToken']||$device->user_id!=$userId)
                    {
                        DB::table('devices')->where('device_vendor_uuid' , $deviceFields['vendorUUID'])->update(array('user_id'=>$userId ,'device_id'=>$deviceFields['deviceToken'] , 'device_updated_at' => $dateTime));
                    }
                }
            }
        }else{
            $deviceId = isset($deviceFields['deviceToken'])?$deviceFields['deviceToken']:'';//
            $appShortVersion = isset($deviceFields['appShortVersion'])?$deviceFields['appShortVersion']:'';//
            $appVersion = isset($deviceFields['appVersion'])?$deviceFields['appVersion']:'';//
            $appBundleIdentifier = isset($deviceFields['appBundleIdentifier'])?$deviceFields['appBundleIdentifier']:'';
            $vendorUUID = $deviceFields['vendorUUID'];
            $systemName = isset($deviceFields['systemName'])?$deviceFields['systemName']:'';
            $systemVersion = isset($deviceFields['systemVersion'])?$deviceFields['systemVersion']:'';
            $phoneName = isset($deviceFields['phoneName'])?$deviceFields['phoneName']:'';
            $phoneModel = isset($deviceFields['phoneModel'])?$deviceFields['phoneModel']:'';
            $localizedModel = isset($deviceFields['localizedModel'])?$deviceFields['localizedModel']:'';
            $networkType = isset($deviceFields['networkType'])?$deviceFields['networkType']:'';;
            $carrierName = isset($deviceFields['carrierName'])?$deviceFields['carrierName']:'';
            $deviceType = $deviceFields['deviceType'];
            $devicePlatformName = isset($deviceFields['devicePlatformName'])?$deviceFields['devicePlatformName']:'';
            $device= DB::table('devices')->where('device_vendor_uuid' , $vendorUUID)->first();
            if(empty($device))
            {
                $deviceData = array(
                    'device_id' => $deviceId,//
                    'device_app_short_version' => $appShortVersion,//
                    'device_app_version' => $appVersion,//
                    'device_app_bundle_identifier' => $appBundleIdentifier,//
                    'device_vendor_uuid' => $vendorUUID,//
                    'device_system_name' => $systemName,//
                    'device_system_version' => $systemVersion,//
                    'device_platform_name' => $devicePlatformName,//
                    'device_phone_name' => $phoneName,
                    'device_phone_model' => $phoneModel,//
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
}
