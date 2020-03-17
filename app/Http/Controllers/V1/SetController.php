<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;


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
        Cache::forget('fine_post');
        app(PostRepository::class)->getFinePostIds();
        return $this->response->noContent();
    }

    public function dxSwitch()
    {
        $value = Cache::remember('dxSwitch', 100 , function() {
            return 1;
        });
        return $this->response->array(array('switch'=>$value));
    }

    public function clearDxCache(Request $request)
    {
        $switch = intval($request->input('switch' , 1));
        Cache::remember('dxSwitch', 100 , function() use ($switch) {
            return $switch;
        });
        return $this->response->noContent();
    }

}
