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
        $signature = strval($request->input('signature'));
        if(!empty($signature))
        {
            $app_signature = app_signature($params);
            if($signature==$app_signature)
            {
                $app = new App();
                $app = $app->where('platform' , $params['platform'])->orderBy('id' , 'DESC')->first();
                return $this->response->array($app);
            }
        }
        return $this->response->noContent();
    }

    public function clearCache(Request $request)
    {
        Cache::forget('fine_post');
        app(PostRepository::class)->getFinePostIds();
        return $this->response->noContent();
    }

}
