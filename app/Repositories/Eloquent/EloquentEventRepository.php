<?php

/**
 * @Author: Dell
 * @Date:   2019-08-09 20:17:56
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-08-09 20:18:57
 */
namespace App\Repositories\Eloquent;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use App\Repositories\EloquentBaseRepository;
use App\Repositories\Contracts\EventRepository;


class EloquentEventRepository  extends EloquentBaseRepository implements EventRepository
{
    /**
     * @note 获取当前活动
     * @datetime 2021-07-12 19:12
     * @return array|mixed
     */
    public function getActiveEvent()
    {
        $key = 'helloo:event:active:event';
        $data = Redis::get($key);
        $events = array();
        if(!blank($data))
        {
            $events = \json_decode($data , true);
        }
        if(blank($events))
        {
            $now = Carbon::now()->timestamp;
            $events = $this->model->where('status' , 1)->where('ended_at', '>=' , $now)->orderByDesc('created_at')->get();
            $events = collect($events)->toArray();
            Redis::set($key , \json_encode($events , JSON_UNESCAPED_UNICODE));
            Redis::expire($key , 60*60*24);
        }
        return $events;
    }

    /**
     * @note 更新当前活动
     * @datetime 2021-07-12 19:12
     * @return array
     */
    public function updateActiveEvent()
    {
        $key = 'helloo:event:active:event';
        Redis::del($key);
        $now = Carbon::now()->timestamp;
        $events = $this->model->where('status' , 1)->where('started_at', '>=' , $now)->where('ended_at', '<=' , $now)->orderByDesc('created_at')->get();
        $events = collect($events)->toArray();
        Redis::set($key , \json_encode($events , JSON_UNESCAPED_UNICODE));
        Redis::expire($key , 60*60*24*60);
        return $events;
    }
}
