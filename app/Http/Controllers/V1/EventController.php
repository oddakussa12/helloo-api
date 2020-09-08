<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class EventController extends BaseController
{
    //
    public function index()
    {
        $key = 'event_index';
        if(!Redis::exists($key))
        {
            $event = \DB::table('events')->where('status' , 1)->orderByDesc('sort')->select('name' , 'sort' , 'type' , 'image' , 'value')->first();
            if(!blank($event))
            {
                Redis::set($key , \json_encode($event , JSON_UNESCAPED_UNICODE));
                Redis::expire($key , 86400);
            }
        }else{
            $event = \json_decode(Redis::get($key));
        }
        $locale = locale();
        !blank($event)&&$event->image = config('common.qnUploadDomain.thumbnail_domain').(!empty($event->image)?'':(\json_decode($event->image , true))[$locale]);
        return $this->response->array((array)$event);
    }
}
