<?php

namespace App\Http\Controllers\V1;

use App\Models\App;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;


class AppController extends BaseController
{

    public function index(Request $request)
    {
        $params = $request->only('platform' , 'version' , 'time_stamp');
        $signature = strtolower(strval($request->input('signature')));
        if(!empty($signature))
        {
            $app_signature = app_signature($params);
            if($signature==$app_signature)
            {
                $app = $this->getFirstApp();
                $platform = $app[$params['platform']];
                $platform->isUpgrade = version_compare($params['version'] , $platform['version'] , '<');
                return $this->response->array($platform);
            }
        }
        return $this->response->noContent();
    }

    protected function getFirstApp()
    {
        $ios = 0;
        $android = 1;
        return Cache::rememberForever('lastVersionApp' , function() use ($ios , $android){
            $app = new App();
            $ios_app = $app->where('platform' , $ios)->orderBy('id' , 'DESC')->first();
            $android_app = $app->where('platform' , $android)->orderBy('id' , 'DESC')->first();
            return array($ios_app , $android_app);
        });
    }

    public function clearCache(Request $request)
    {
        Cache::forget('lastVersionApp');
        $this->getFirstApp();
        return $this->response->noContent();
    }

}
