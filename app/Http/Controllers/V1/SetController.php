<?php

namespace App\Http\Controllers\V1;

use App\Custom\Constant\Constant;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;


class SetController extends BaseController
{

    public function postRate(Request $request)
    {
        $params = $request->only('referer' , 'time_stamp');
        $rate = floatval($request->input('rate' , 0));
        $signature = strtolower(strval($request->input('signature')));
        $app_signature = '';
        if(!empty($signature))
        {
            $params['rate'] = $rate;
            $app_signature = common_signature($params);
            if($signature==$app_signature)
            {
                post_gravity($rate);
                return $this->response->noContent();
            }
        }
        $error = \json_encode(
            array(
                'params'=>$params ,
                'rate'=>$rate ,
                'signature'=>$signature ,
                'app_signature'=>$app_signature
            )
        );
        return $this->response->errorBadRequest($error);

    }

    protected function getFirstApp()
    {
        $ios = 0;
        $android = 1;
        Cache::forget('lastVersionApp');
        return Cache::rememberForever('lastVersionApp' , function() use ($ios , $android){
            $app = new App();
            $ios_app = $app->where('platform' , $ios)->orderBy('id' , 'DESC')->first();
            $android_app = $app->where('platform' , $android)->orderBy('id' , 'DESC')->first();
            return array($ios_app , $android_app);
        });
    }

    public function clearCache()
    {
        return $this->response->noContent();
    }

    public function dxSwitch()
    {
        $agent = new Agent();
        if($agent->match('YooulAndroid'))
        {
            $key = 'dxSwitchAndroid';
        }else{
            $key = 'dxSwitchIos';
        }
        return $this->response->array(array_merge(dx_uuid(), dx_switch($key) , array('type'=>$key)));
    }

    public function clearDxCache(Request $request)
    {
        $switch = intval($request->input('switch' , 0));
        $post_uuid = strval($request->input('post_uuid' , ''));
        $type = strval($request->input('type' , 'android'));
        if($type=='android')
        {
            $key = 'dxSwitchAndroid';
        }else{
            $key = 'dxSwitchIos';
        }
        dx_switch($key , $switch);
        dx_uuid($post_uuid);
        return $this->response->noContent();
    }

    public function commonSwitch(Request $request)
    {
        $agent = new Agent();
        $key = 'commonSwitch';
        if(!Redis::exists($key)) {
            $value = [
                'free_translator' => 1,
                'refer_friend'    => 1,
                'carousel'        => 0,
                'test_translator' => 0,
                'av'              => 1,
                'heart_progress'  => Constant::CHAT_SUM_STAR,
                'emoji_md5'       => config('common.emoji_md5')
            ];
            Redis::hmset($key , $value);
        }
        $fieldStr = (string)$request->input('include' , '');
        $fields   = explode(',' , $fieldStr);
        $values   = Redis::hmget($key , $fields);
        $switches = array_combine($fields , $values);
        $switches = array_filter ($switches , function($v , $k) {
            return !empty($k)&&$v!==null;
        } , ARRAY_FILTER_USE_BOTH );

        if ($agent->match('YooulAndroid')) {
            $appVersion  = 1;
            $dxSwitchKey = 'dxSwitchAndroid';
        } else {
            $appVersion  = 0;
            $dxSwitchKey = 'dxSwitchIos';
        }

        if (in_array('dx_switch', $fields)) {
            $switches['dx_switch'] = array_merge(dx_uuid(), dx_switch($key) , array('type'=>$dxSwitchKey));
        }
        if (strpos($fieldStr ,'emoji_md5')) {
            $switches['emoji_md5'] = config('common.emoji_md5');
        }
        if(strpos($fieldStr ,'index_switch'))
        {
            $switches['index_switch'] = (bool)Redis::get('index_switch');
        }

        if(strpos($fieldStr ,'app_version')) {
            $version                 = (string)$request->input('version' , $agent->getHttpHeader('YooulVersion'));
            $app                     = app(AppController::class)->getFirstApp();
            $platform                = $app[$appVersion];
            $platform->isUpgrade     = version_compare($version , $platform['version'] , '<');
            $switches['app_version'] = $platform;
        }
        return $this->response->array($switches);
    }

}
