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
        $deviceFields['deviceCountry'] = !empty($deviceFields['deviceCountry']) ? $deviceFields['deviceCountry'] : geoip(getRequestIpAddress())->iso_code;
        $this->deviceFields = $deviceFields;
        $this->type = $type;
        if(auth()->check()) {
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

        $deviceType   = (isset($deviceFields['referer']) && $deviceFields['referer']=='iOS') ? 1 : 2;

        if(!empty($deviceFields['registrationId'])) {
            $registrationId = $deviceFields['registrationId'];
            $deviceData     = [
                'device_registration_id'   => $registrationId,
                'device_id'                => $deviceFields['deviceToken']         ?? '',
                'device_app_version'       => $deviceFields['appVersion']          ?? '',
                'device_system_name'       => $deviceFields['systemName']          ?? '',
                'device_system_version'    => $deviceFields['systemVersion']       ?? '',
                'device_platform_name'     => $deviceFields['devicePlatformName']  ?? '',
                'device_phone_name'        => $deviceFields['phoneName']           ?? '',
                'device_phone_model'       => $deviceFields['phoneModel']          ?? '',
                'device_localized_model'   => $deviceFields['localizedModel']      ?? '',
                'device_network_type'      => $deviceFields['networkType']         ?? '',
                'device_carrier_name'      => $deviceFields['carrierName']         ?? '',
                'device_app_short_version' => $deviceFields['appShortVersion']     ?? '',
                'device_language'          => $deviceFields['deviceLanguage']      ?? 'en',
                'device_vendor_uuid'       => $deviceFields['vendorUUID']          ?? '',
                'device_register_type'     => $deviceFields['deviceRegisterType']  ?? 'jpush',
                'device_country'           => strtolower($deviceFields['deviceCountry']),
                'device_type'              => $deviceType,
                'device_created_at'        => $dateTime,
                'device_updated_at'        => $dateTime,
                'device_app_bundle_identifier' => $deviceFields['appBundleIdentifier'] ?? '',
            ];
            if($type == 'signUpOrIn' || !empty($this->userId)) {
                $userId = $this->userId;
                $user   = DB::table('devices')->where('user_id', $userId)->where('device_registration_id', $registrationId)->first();

                if(empty($user)) {
                    $deviceData['user_id'] = $userId;
                    DB::table('devices')->insert($deviceData);
                } else{
                    $data = ['device_updated_at' => $dateTime];
                    DB::table('devices')->where('id', $user->id)->update($data);
                }
            } else {
                $deviceId = $deviceFields['deviceToken'] ?? '';
                $device   = DB::table('devices_infos')->where('device_registration_id', $registrationId)->first();
                if(empty($device)) {
                    unset($deviceData['device_updated_at']);
                    $deviceData['device_created_at'] = time();
                    $deviceData['device_type']       = $deviceFields['deviceType'] ?? '';
                    DB::table('devices_infos')->insert($deviceData);
                } else {
                    if($device->device_id != $deviceId) {
                        DB::table('devices_infos')->where('id', $device->id)->update(['device_id'=>$deviceId]);
                    }
                }
            }
        }

    }
}
