<?php

namespace App\Http\Controllers\V1;

use App\Models\App;
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
        $app = $this->getFirstApp();
        $platform = $app[$platform];
        if(empty($platform))
        {
            return $this->response->noContent();
        }
        $platform['isUpgrade'] = version_compare($version , $platform['version'] , '<');
        $platform['mustUpgrade'] = version_compare($version , $platform['last'] , '<');
        return $this->response->array(array('data'=>$platform));
    }

    public function getFirstApp()
    {
        $lastVersion = 'helloo:app:service:last-version';
        if(Redis::exists($lastVersion))
        {
//            return \json_decode(Redis::get($lastVersion) , true);
        }
        $ios = 'ios';
        $android = 'android';
        $app = new App();
        $ios_app = collect($app->where('platform' , $ios)->orderBy('id' , 'DESC')->first())->toArray();
        $android_app = collect($app->where('platform' , $android)->orderBy('id' , 'DESC')->first())->toArray();
        $data = array('ios'=>$ios_app , 'android'=>$android_app);
        Redis::set($lastVersion , json_encode($data));
        return $data;
    }

    public function mode($model)
    {
        $status = $model=='in'?1:0;
        DB::table('ry_online_users')->where('user_id' , auth()->id())->update(array('status'=>$status));
        return $this->response->accepted();
    }

}
