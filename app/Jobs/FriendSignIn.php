<?php

namespace App\Jobs;

use App\Custom\Constant\Constant;
use App\Models\UserFriend;
use App\Models\UserFriendSignIn;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Class Friend
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
        if (empty($raw)) {
            Log::error(__FILE__. ' message:::::data is  empty  data is  empty');
        }

        list($userId, $friendId) = self::sortId($raw['fromUserId'], $raw['toUserId']); // 排序

        $nextDay  = strtotime(date('Y-m-d',strtotime('+1 day'))); // 获取明天凌晨的时间戳
        $isFriend = self::isFriend($raw['fromUserId'], $raw['toUserId']); // 是否是好友
        if (empty($isFriend)) {
            return false;
        }

        $memKey   = Constant::RY_CHAT_FRIEND_SIGN_IN. $userId.'_'.$friendId;
        $value    = Redis::get($memKey);

        if (!empty($value)) {
            $value = json_decode($value, true);
            if (empty($value['status']) && !in_array($raw['fromUserId'], $value['signUser'])) {
                $value['signUser'][] = $raw['fromUserId'];
                $value['status'] = 1;
                Redis::set($memKey, json_encode($value, JSON_UNESCAPED_UNICODE));
                Redis::expire($memKey, $nextDay - time());

                dump('签到设置缓存');
                // 签到成功 清空好友首页缓存
                Redis::del(Constant::FRIEND_RELATIONSHIP_MAIN.$userId.'_'.$friendId);

                // 签到成功  ----  入库操作
                $data = ['user_id'=>$userId,'friend_id'=>$friendId,'created_at'=>time(),'sign_month'=>date('Ym'),'sign_day'=>strtotime(date('Ymd'))];
                UserFriendSignIn::insert($data);
            }
            dump('签到读取缓存', $value);
        } else {
            $memValue['signUser'] = [$raw['fromUserId']];
            $memValue['status']   = 0;
            dump($memValue);
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
            $mValue['user']   = $userFriend;
            $mValue['friend'] = $friendUser;

            Redis::set($mKey, json_encode($mValue, JSON_UNESCAPED_UNICODE));
            return true;
        } else {
            return $mValue;
        }
    }

    public static function sortId($userId, $friend_id)
    {
        $arr = [$userId, $friend_id];
        sort($arr);
        return $arr;
    }
}
