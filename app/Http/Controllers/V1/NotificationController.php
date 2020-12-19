<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Resources\SystemNotificationCollection;
use Ramsey\Uuid\Uuid;

class NotificationController extends BaseController
{
    public function system(Request $request)
    {
        $systems = SystemNotification::where('is_expired' , 0)->where('type' , 'video')->orderByDesc('created_at')->paginate(20);
        return SystemNotificationCollection::collection($systems);
    }

    public function last(Request $request)
    {
        $pullTime = intval($request->input('pull_time' , 0));
        $lastSystemNotification = SystemNotification::where('is_expired' , 0)->where('type' , 'video')->orderByDesc('created_at')->first();
        if(blank($lastSystemNotification))
        {
            return $this->response->array(array('data'=>array()));
        }else{
            if($pullTime===0)
            {
                $lastSystemNotification->isNew = true;
            }else{
                $lastCreatedAt = optional($lastSystemNotification->created_at)->timestamp;
                $lastSystemNotification->isNew = boolval($pullTime<$lastCreatedAt);
            }
            return new SystemNotificationCollection($lastSystemNotification);
        }
    }

    public function new(Request $request)
    {
        $appends = array();
        $notificationId = strval($request->input('notification_id' , ''));
        $systemNotifications = SystemNotification::where('is_expired' , 0)->where('type' , 'video');
        if(!blank($notificationId))
        {
            $appends['notification_id'] = $notificationId;
            $systemNotification = SystemNotification::where('notification_id' , $notificationId)->first();
            if(blank($systemNotification))
            {
                abort(404);
            }
            $systemNotifications = $systemNotifications->where('id' , '>' , $systemNotification->id);
            $systems = $systemNotifications->orderByDesc('created_at')->paginate(20)->appends($appends);
            return SystemNotificationCollection::collection($systems);
        }else{
            $systems = $systemNotifications->orderByDesc('created_at')->paginate(1)->appends($appends);
            return SystemNotificationCollection::collection($systems);
        }

    }

    public function notificationCount()
    {
//        return SystemNotification::where('is_expired' , 0)->where('created_at' , '>' , $pullTime)
    }

    public function bulkInsert()
    {
        $types = array('text' , 'image' , 'video' , 'url');
        for ($i=1;$i<=123;$i++)
        {
            $now = Carbon::now()->subHours(mt_rand(1 , 125))->subMinute(mt_rand(1,120))->subSeconds(mt_rand(1, 120))->timestamp;
            DB::table('system_notifications')->insert(array(
                'notification_id'=>Uuid::uuid1()->toString(),
                'from_id'=>0,
                'type'=>$types[mt_rand(0,3)],
                'title'=>'title'.$i,
                'content'=>'content'.$i,
                'cover_url'=>'https://qnidyooulimage.mmantou.cn/Fqu7CQGZYj63QS7l__Kq8S5QqHeC.jpg?imageView2/5/w/192/h/192/interlace/1|imageslim',
                'video_url'=>'https://f.video.weibocdn.com/R3Rjmslrlx07IsJmPztu01041200743K0E010.mp4?label=mp4_hd&template=480x640.24.0&trans_finger=7c347e6ee1691b93dc7e5726f4ef34b3&ori=0&ps=1EO8O2oFB1ePdo&Expires=1606974751&ssig=9csHBh3jaL&KID=unistore,video',
                'jump_url'=>'https://baidu.com',
                'created_at'=>$now,
                'updated_at'=>$now,
                'expired_at'=>Carbon::now()->addDays(7)->timestamp,
            ));
        }

    }



}
