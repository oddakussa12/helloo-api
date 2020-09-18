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
            $event = \DB::table('events')->where('status' , 1)->orderByDesc('sort')->select('name' , 'sort' , 'type' , 'image' , 'value' , 'flag')->first();
            if(!blank($event))
            {
                Redis::set($key , \json_encode($event , JSON_UNESCAPED_UNICODE));
                Redis::expire($key , 86400);
            }
        }else{
            $event = \json_decode(Redis::get($key));
        }
        $locale = locale();
        if(!blank($event))
        {
            $image = \json_decode($event->image , true);
            $event->image = $image;
            if(isset($image[$locale]))
            {
                $event->image = config('common.qnUploadDomain.thumbnail_domain').$image[$locale].'?imageMogr2/auto-orient/interlace/1|imageslim';
            }else{
                if(isset($image['en']))
                {
                    $event->image = config('common.qnUploadDomain.thumbnail_domain').$image['en'].'?imageMogr2/auto-orient/interlace/1|imageslim';
                }
            }
            (!isset($image[$locale])&&!isset($image['en']))&&$event=array();
            if(!empty($event))
            {
                if(isset($event->type)&&$event->type=='h5')
                {
                    $value = $event->value;
                    $ip = getRequestIpAddress();
                    $country = geoip($ip)->iso_code;
                    $event->value = $value."?country=".$country;
                }
            }
        }
        return $this->response->array((array)$event);
    }
}
