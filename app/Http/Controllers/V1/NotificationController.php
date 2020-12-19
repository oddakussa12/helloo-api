<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\SystemNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Resources\SystemNotificationCollection;

class NotificationController extends BaseController
{
    public function system(Request $request)
    {
        $systems = SystemNotification::where('is_expired' , 0)->orderByDesc('created_at')->paginate(20);
        return SystemNotificationCollection::collection($systems);
    }

    public function last()
    {
        $systemNotification = SystemNotification::where('is_expired' , 0)->orderByDesc('created_at')->first();
//        if(!blank($systemNotification))
//        {
//            $read = DB::table('read_system_notifications')->where('user_id' , auth()->id())->where('notification_id' , $systemNotification->notification_id)->first();
//            $systemNotification->is_read = !blank($read);
//        }
//        $count = count(DB::table('system_notifications_read_counts')->where('user_id' , auth()->id())->first());

        return new SystemNotificationCollection($systemNotification);
    }

    public function new(Request $request)
    {
        $pullTime = intval($request->input('pull_time' , time()));
        $createdAt = optional(auth()->user()->user_created_at)->timestamp;
        if($pullTime<$createdAt)
        {
            $pullTime = $createdAt;
        }
        $systems = SystemNotification::where('is_expired' , 0)->where('created_at' , '>' , $pullTime)->orderByDesc('created_at')->paginate(20);
        return SystemNotificationCollection::collection($systems);
    }

    public function notificationCount()
    {
//        return SystemNotification::where('is_expired' , 0)->where('created_at' , '>' , $pullTime)
    }



}
