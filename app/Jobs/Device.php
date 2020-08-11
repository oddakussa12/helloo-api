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
        $dateTime     = Carbon::now();
        $type         = $this->type;
        $deviceFields = $this->deviceFields;

        if(!empty($deviceFields['registrationId']))
        {
            $registrationId = $deviceFields['registrationId'];
            $deviceLanguage = $deviceFields['deviceLanguage'] ?: 'en';
            if($type=='signUpOrIn')
            {
                $deviceType = (isset($deviceFields['referer']) && $deviceFields['referer']=='iOS') ? 1 : 2;
                $userId     = $this->userId;
                $user       = DB::table('devices')->where('user_id', $userId)->where('device_registration_id', $registrationId)->first();
                if(empty($user))
                {
                    $data = [
                        'device_registration_id' => $registrationId ,
                        'user_id'           => $userId ,
                        'device_type'       => $deviceType ,
                        'device_language'   => $deviceLanguage ,
                        'device_created_at' => $dateTime ,
                        'device_updated_at' => $dateTime
                    ];

                    if(isset($deviceFields['deviceToken']))
                    {
                        $data['device_id'] = $deviceFields['deviceToken'];
                    }
                    if(isset($deviceFields['vendorUUID']))
                    {
                        $data['device_vendor_uuid'] = $deviceFields['vendorUUID'];
                    }
                    DB::table('devices')->insert($data);
                } else {
                    $data = ['device_updated_at' => $dateTime];
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
            } else {
                $deviceId = $deviceFields['deviceToken'] ?: '';
                $device   = DB::table('devices')->where('device_registration_id', $registrationId)->first();
                if(empty($device))
                {
                    $deviceData = [
                        'device_id'                => $deviceId,
                        'device_registration_id'   => $registrationId,
                        'device_app_version'       => $deviceFields['appShortVersion']     ?: '',
                        'device_system_name'       => $deviceFields['systemName']          ?: '',
                        'device_system_version'    => $deviceFields['systemVersion']       ?: '',
                        'device_platform_name'     => $deviceFields['devicePlatformName']  ?: '',
                        'device_phone_name'        => $deviceFields['phoneName']           ?: '',
                        'device_phone_model'       => $deviceFields['phoneModel']          ?: '',
                        'device_localized_model'   => $deviceFields['localizedModel']      ?: '',
                        'device_network_type'      => $deviceFields['networkType']         ?: '',
                        'device_carrier_name'      => $deviceFields['carrierName']         ?: '',
                        'device_app_short_version' => $deviceFields['appShortVersion']     ?: '',
                        'device_type'              => $deviceFields['deviceType'],
                        'device_vendor_uuid'       => $deviceFields['vendorUUID'],
                        'device_language'          => $deviceLanguage,
                        'device_created_at'        => time(),
                        'device_app_bundle_identifier' => $deviceFields['appBundleIdentifier'] ?: '',
                    ];
                    DB::table('devices_infos')->insert($deviceData);
                } else {
                    if($device->device_id != $deviceId)
                    {
                        DB::table('devices_infos')->where('id', $device->id)->update(['device_id'=>$deviceId]);
                    }
                }
            }
        }

    }
}
