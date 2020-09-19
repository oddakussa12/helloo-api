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
            $event = collect($event)->toArray();
            if(!blank($event))
            {
                Redis::set($key , \json_encode($event , JSON_UNESCAPED_UNICODE));
                Redis::expire($key , 86400);
            }
        }else{
            $event = \json_decode(Redis::get($key) , true);
        }
        $locale = locale();
        $ip = getRequestIpAddress();
        $country = geoip($ip)->iso_code;
        $domain = config('common.qnUploadDomain.thumbnail_domain');
        if(!blank($event)&&!empty($event['image']))
        {
            if(isset($event['type'])&&$event['type']=='h5')
            {
                $value = $event['value'];
                $event['value'] = $value."?country=".$country."&language=".$locale."&time=".time();
            }
            $image = \json_decode($event['image'] , true);
            if(isset($image[$locale]))
            {
                $event['image'] = $domain.$image[$locale].'?imageMogr2/auto-orient/interlace/1|imageslim';
            }else{
                if(isset($image['en']))
                {
                    $event['image'] = $domain.$image['en'].'?imageMogr2/auto-orient/interlace/1|imageslim';
                }else{
                    $event = array();
                }
            }
        }
        return $this->response->array((array)$event);
    }
}
