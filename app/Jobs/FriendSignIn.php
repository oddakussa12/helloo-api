<?php

namespace App\Jobs;

use App\Custom\Constant\Constant;
use App\Models\UserFriend;
use App\Models\UserFriendSignIn;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Class FriendSignIn
 * @package App\Jobs
 * 朋友每日签到
 */
class FriendSignIn implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
        //$this->handle();
    }

    /**
     * Execute the job.
     *
     * @return void
     * 好友签到
     */
    public function handle()
    {
        $raw      = $this->data;
        $nextDay  = strtotime(date('Y-m-d',strtotime('+1 day'))); // 获取明天凌晨的时间戳
        $isFriend = self::isFriend($raw['fromUserId'], $raw['toUserId']); // 是否是好友
        $arr      = self::sortId($raw['fromUserId'], $raw['toUserId']); // 排序

        $friend_uuid = implode('_', $arr);
        $memKey      = Constant::RY_CHAT_FRIEND_SIGN_IN. $friend_uuid;
        $value       = Redis::get($memKey);

        if (!empty($value)) {
            $value = json_decode($value, true);
            if (empty($value['status']) && !in_array($raw['fromUserId'], $value['signUser'])) {
                $value['signUser'][] = $raw['fromUserId'];
                $value['status'] = 1;
                Redis::set($memKey, json_encode($value, JSON_UNESCAPED_UNICODE));
                Redis::expire($memKey, $nextDay - time());

                // 签到成功  ----  入库操作
                $data[] = ['user_id'=>$raw['fromUserId'],'friend_id'=>$raw['toUserId'],'created_at'=>time(),'sign_month'=>date('Ym'),'sign_month'=>strtotime(date('Ymd'))];
                $data[] = ['user_id'=>$raw['toUserId'],'friend_id'=>$raw['fromUserId'],'created_at'=>time(),'sign_month'=>date('Ym'),'sign_month'=>strtotime(date('Ymd'))];
               $isFriend && UserFriendSignIn::insert($data);
            }
        } else {
            $memValue['signUser'] = [$raw['fromUserId']];
            $memValue['status']   = 0;

            Redis::set($memKey, json_encode($memValue, JSON_UNESCAPED_UNICODE));
            Redis::expire($memKey, $nextDay - time());
        }
    }

    /**
     * @param $userId
     * @param $friendId
     * @return bool
     * 查询是否有朋友关系
     */
    public static function isFriend($userId, $friendId)
    {
        $arr     = self::sortId($userId, $friendId);
        $mKey    = Constant::RY_CHAT_FRIEND_IS_FRIEND. implode('_', $arr);
        $mValue  = Redis::get($mKey);
        if (empty($mValue)) {
            $userFriend = UserFriend::where('user_id', $userId)->where('friend_id', $friendId)->first();
            $friendUser = UserFriend::where('user_id', $friendId)->where('friend_id', $userId)->first();
            if (empty($userFriend) || empty($friendUser)) return false;
            Redis::set($mKey, 1);
            return true;
        } else {
            return $mValue;
        }
    }

    public static function sortId($user_id, $friend_id)
    {
        $arr = [$user_id, $friend_id];
        sort($arr);
        return $arr;
    }
}
