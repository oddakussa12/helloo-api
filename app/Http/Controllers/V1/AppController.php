<?php

namespace App\Http\Controllers\V1;

use App\Models\App;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;


class AppController extends BaseController
{

    public function index(Request $request)
    {
        $agent = new Agent();
        $version = strval($request->input('version' , $agent->getHttpHeader('HellooVersion')));
        $params = $request->only('platform' , 'version' , 'time_stamp');
        $platform = strtolower(strval($params['platform']??''));
        $platform = in_array($platform , array('ios' , 'android'))?$platform:'android';
        $app = $this->getFirstApp();
        $platform = $app[$platform];
        if(empty($platform))
        {
            return $this->response->noContent();
        }
        $platform['isUpgrade'] = version_compare($version , $platform['version'] , '<');
        return $this->response->array($platform);
    }

    public function home(Request $request)
    {
        $agent = new Agent();
        $version = strval($request->input('version' , $agent->getHttpHeader('HellooVersion')));
        $params = $request->only('platform' , 'version' , 'time_stamp');
        $platform = strtolower(strval($params['platform']??''));
        $platform = in_array($platform , array('ios' , 'android'))?$platform:'android';
        $app = $this->getApp();
        $platform = $app[$platform];
        if(empty($platform))
        {
            return $this->response->noContent();
        }
        $platform['isUpgrade'] = version_compare($version , $platform['version'] , '<');
        $platform['mustUpgrade'] = version_compare($version , $platform['last'] , '<');
        return $this->response->array(array('data'=>$platform));
    }

    public function getApp()
    {
        $lastVersion = 'helloo:app:service:new-version';
        if(Redis::exists($lastVersion))
        {
            return \json_decode(Redis::get($lastVersion) , true);
        }
        $ios = 'ios';
        $android = 'android';
        $app = new App();
        $ios_app = collect($app->where('platform' , $ios)->orderBy('id' , 'DESC')->first())->toArray();
        $android_app = collect($app->where('platform' , $android)->orderBy('id' , 'DESC')->first())->toArray();
        $data = array('ios'=>$ios_app , 'android'=>$android_app);
        Redis::set($lastVersion , json_encode($data));
        Redis::expire($lastVersion , 2592000);
        return $data;
    }

    public function getFirstApp()
    {
        $lastVersion = 'helloo:app:service:last-version';
        if(Redis::exists($lastVersion))
        {
            return \json_decode(Redis::get($lastVersion) , true);
        }
        $ios = 'ios';
        $android = 'android';
        $app = new App();
        $ios_app = collect($app->where('platform' , $ios)->orderBy('id' , 'DESC')->first())->toArray();
        $android_app = collect($app->where('platform' , $android)->orderBy('id' , 'DESC')->first())->toArray();
        $data = array('ios'=>$ios_app , 'android'=>$android_app);
        Redis::set($lastVersion , json_encode($data));
        Redis::expire($lastVersion , 2592000);
        return $data;
    }

    public function mode($model)
    {
        $status = $model=='in'?1:0;
        DB::table('ry_online_users')->where('user_id' , auth()->id())->update(array('status'=>$status));
        return $this->response->accepted();
    }

    public function referrer(Request $request)
    {
        $package_name = strval($request->input('package_name' , ''));
        $installReferrer = strval($request->input('install_referrer' , ''));
        $referrerClickTimestampSeconds = intval($request->input('referrer_click_timestamp_seconds' , 0));
        $installBeginTimestampSeconds = intval($request->input('install_begin_timestamp_seconds' , 0));
        $referrerClickTimestampServerSeconds = intval($request->input('referrer_click_timestamp_server_seconds' , 0));
        $installBeginTimestampServerSeconds = intval($request->input('install_begin_timestamp_server_seconds' , 0));
        $installVersion = strval($request->input('install_version' , ''));
        $googlePlayInstant = $request->input('google_play_instant');
        $agent = new Agent();
        $version = $agent->getHttpHeader('HellooVersion');
        $deviceId = $agent->getHttpHeader('deviceId');
        $brand = strval($request->input('brand' ,''));
        $model = strval($request->input('model' ,''));
        $resolution = strval($request->input('resolution' ,''));
        $provider = strval($request->input('provider' ,''));
        $systemVersion = strval($request->input('system_version' ,''));
        $system = strval($request->input('system' ,''));
        $network = strval($request->input('network' ,''));
        $language = strval($request->input('language' ,''));
        if(!empty($package_name)&&!empty($version)&&!empty($deviceId))
        {
            $data = array(
                'package_name'=>$package_name,
                'app_version'=>$version,
                'device_id'=>$deviceId,
                'ip'=>getRequestIpAddress(),
                'created_at'=>date('Y-m-d H:i:s'),
            );
            !empty($installReferrer)&&$data['install_referrer'] = $installReferrer;
            !empty($referrerClickTimestampSeconds)&&$data['referrer_click_timestamp_seconds'] = $referrerClickTimestampSeconds;
            !empty($installBeginTimestampSeconds)&&$data['install_begin_timestamp_seconds'] = $installBeginTimestampSeconds;
            !empty($referrerClickTimestampServerSeconds)&&$data['referrer_click_timestamp_server_seconds'] = $referrerClickTimestampServerSeconds;
            !empty($installBeginTimestampServerSeconds)&&$data['install_begin_timestamp_server_seconds'] = $installBeginTimestampServerSeconds;
            !empty($installVersion)&&$data['install_version'] = $installVersion;
            !empty($googlePlayInstant)&&$data['google_play_instant'] = $googlePlayInstant;
            !empty($brand)&&$data['brand'] = $brand;
            !empty($model)&&$data['model'] = $model;
            !empty($resolution)&&$data['resolution'] = $resolution;
            !empty($provider)&&$data['provider'] = $provider;
            !empty($systemVersion)&&$data['system_version'] = $systemVersion;
            !empty($system)&&$data['system'] = $system;
            !empty($network)&&$data['network'] = $network;
            !empty($language)&&$data['language'] = $language;
            DB::table('devices')->insert($data);
        }
        return $this->response->created();
    }

}
