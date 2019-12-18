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
        if(!empty($deviceFields['registrationId']))
        {
            $registrationId = $deviceFields['registrationId'];//
            $deviceLanguage= isset($deviceFields['deviceLanguage'])?$deviceFields['deviceLanguage']:'en';//
            if($type=='signUpOrIn')
            {
                $deviceType= (isset($deviceFields['referer'])&&$deviceFields['referer']=='iOS')?1:2;//
                $userId = $this->userId;
                $user = DB::table('devices')->where('user_id' , $userId)->first();
                if(empty($user))
                {
                    $device= DB::table('devices')->where('device_registration_id' , $registrationId)->first();
                    if(empty($device))
                    {
                        $data = array(
                            'device_registration_id'=>$registrationId ,
                            'user_id'=>$userId ,
                            'device_type'=>$deviceType ,
                            'device_language'=>$deviceLanguage ,
                            'device_created_at' => $dateTime ,
                            'device_updated_at' => $dateTime
                        );
                        if(isset($deviceFields['deviceToken']))
                        {
                            $data['device_id'] = $deviceFields['deviceToken'];
                        }
                        if(isset($deviceFields['vendorUUID']))
                        {
                            $data['device_vendor_uuid'] = $deviceFields['vendorUUID'];
                        }
                        DB::table('devices')->insert($data);
                    }else{
                        $data = array(
                            'user_id'=>$userId ,
                            'device_updated_at' => $dateTime
                        );
                        if(isset($deviceFields['deviceToken']))
                        {
                            $data['device_id'] = $deviceFields['deviceToken'];
                        }
                        if(isset($deviceFields['vendorUUID']))
                        {
                            $data['device_vendor_uuid'] = $deviceFields['vendorUUID'];
                        }
                        DB::table('devices')->where('device_registration_id' , $registrationId)->update($data);
                    }
                }else{
                    if($user->device_registration_id!=$registrationId)
                    {
                        $data = array(
                            'device_registration_id'=>$registrationId ,
                            'device_type'=>$deviceType ,
                            'device_updated_at' => $dateTime
                        );
                        if(isset($deviceFields['deviceToken']))
                        {
                            $data['device_id'] = $deviceFields['deviceToken'];
                        }
                        if(isset($deviceFields['vendorUUID']))
                        {
                            $data['device_vendor_uuid'] = $deviceFields['vendorUUID'];
                        }
                        DB::table('devices')->where('user_id' , $userId)->update($data);
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
                $device= DB::table('devices')->where('device_registration_id' , $registrationId)->first();
                if(empty($device))
                {
                    $deviceData = array(
                        'device_id' => $deviceId,//
                        'device_registration_id' => $registrationId,//
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
                        'device_language' => $deviceLanguage,
                        'device_created_at' => $dateTime,
                        'device_updated_at' => $dateTime,
                    );
                    DB::table('devices')->insert($deviceData);
                }else{
                    if($device->device_id!=$deviceId)
                    {
                        DB::table('devices')->where('device_registration_id' , $registrationId)->update(array('device_id'=>$deviceId , 'device_updated_at' => $dateTime));
                        //->update(array('device_vendor_uuid'=>$deviceFields['vendorUUID'] , 'user_id'=>$userId ,'device_id'=>$deviceFields['deviceToken'] , 'device_updated_at' => $dateTime));
                    }
                }
            }
        }

    }
}
