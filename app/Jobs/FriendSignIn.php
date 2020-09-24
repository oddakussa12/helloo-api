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

    }

    /**
     * Execute the job.
     *
     * @return void
     * 好友签到
     */
    public function handle()
    {

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

    public static function sortId($userId, $friend_id)
    {
        $arr = [$userId, $friend_id];
        sort($arr);
        return $arr;
    }
}
