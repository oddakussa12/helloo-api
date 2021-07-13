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
    /**
     * @note 系统通知
     * @datetime 2021-07-12 18:57
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function system(Request $request)
    {
        $systems = SystemNotification::where('is_expired' , 0)->where('type' , 'video')->orderByDesc('created_at')->paginate(20);
        return SystemNotificationCollection::collection($systems);
    }

    /**
     * @note 最后一条通知
     * @datetime 2021-07-12 18:57
     * @param Request $request
     * @return SystemNotificationCollection
     */
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

    /**
     * @note 最新通知
     * @datetime 2021-07-12 18:57
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
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

}
