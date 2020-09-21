<?php

namespace App\Jobs;

use App\Custom\Constant\Constant;
use App\Models\PostTranslation as PostTranslationModel;
use App\Models\RyChatRaw;
use App\Models\RyChatFailed;
use App\Models\UserFriend;
use App\Models\UserFriendLevel;
use App\Models\UserFriendRequest;
use App\Models\UserFriendSignIn;
use App\Models\UserFriendTalk;
use App\Models\UserFriendTalkList;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\Rule;
use App\Models\RyChat as RyChats;
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
        $isFriend = $this->isFriend($raw['fromUserId'], $raw['toUserId']); // 是否是好友

        $arr         = self::sortId($raw['fromUserId'], $raw['toUserId']);

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
                $data[] = ['user_id'=>$raw['fromUserId'],'friend_id'=>$raw['toUserId'],'friend_uuid'=>$friend_uuid,'created_at'=>time(),'sign_month'=>date('Ym'),'sign_month'=>date('Ymd')];
                $data[] = ['user_id'=>$raw['toUserId'],'friend_id'=>$raw['fromUserId'],'friend_uuid'=>$friend_uuid,'created_at'=>time(),'sign_month'=>date('Ym'),'sign_month'=>date('Ymd')];
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
    public function isFriend($userId, $friendId)
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


    /**
     * @param $userId
     * @param $friendId
     * @return bool
     * 是否存在特殊好友关系（情侣 基友等）
     */
    public static function isFriendRelation($userId, $friendId)
    {
        $arr     = self::sortId($userId, $friendId);
        list($userId, $friendId) = $arr;

        $memKey   = Constant::RY_CHAT_FRIEND_RELATIONSHIP. explode($arr);
        $memValue = Redis::get($memKey);
        if (empty($memValue)) {
            $result = UserFriendLevel::where(['user_id'=>$userId, 'friend_id'=>$friendId, 'is_delete'=>0])->first();
            if (empty($result)) return false;
            Redis::set($memKey, 1);
        } else {
            return true;
        }
    }


    public static function sortId($user_id, $friend_id)
    {
        $arr = [$user_id, $friend_id];
        sort($arr);
        return $arr;
    }

    /**
     * @return bool
     * 创建特殊好友关系
     */
    public function handle2()
    {
        $raw = $this->data;

        $isFriend = $this->isFriend($raw['fromUserId'], $raw['toUserId']);
        if (empty($isFriend)) {
            return false;
        }
        $today = date('Ymd');
        $isFriendRelation = $this->isFriendRelation($raw['fromUserId'], $raw['toUserId']);

        list($userId, $friend_id) = $arr = self::sortId($raw['fromUserId'], $raw['toUserId']);

        // 插入总表
        /*$result  = UserFriendTalk::where(['user_id'=>$userId, 'friend_id'=>$friend_id, 'is_delete'=>0])->get();
        $uNum    = $result->user_id   == $raw['fromUserId'] ? 1 : 0;
        $fNum    = $result->friend_id == $raw['fromUserId'] ? 1 : 0;
        UserFriendTalk::updateOrCreate(['id' => $result->getKey()],
            ['user_id_count'=>$result->user_id_count+$uNum, 'friend_id_count'=>$result->friend_id_count+$fNum]
        );*/

        list($userId, $friend_id) = FriendSignIn::sortId($raw['fromUserId'], $raw['toUserId']);

        $today  = date('Ymd');
        $result = UserFriendTalkList::where(['user_id'=>$userId, 'friend_id'=>$friend_id, 'is_delete'=>0])->first();
        $uNum   = $fNum = $score = 0;

        if (!empty($result)) {
            $uNum    = $result['user_id']   == $raw['fromUserId'] ? $result['user_id_count']+1   : $result['user_id_count'];
            $fNum    = $result['friend_id'] == $raw['fromUserId'] ? $result['friend_id_count']+1 : $result['friend_id_count'];
            $score   = $result['talk_day']  == $today  ? $result['score']             : 0;
            $count   = array_sum([$uNum, $fNum]);
            if (!empty($uNum) && !empty($fNum) && $count>=20) {
                dump(2);
                $uNum  = $fNum = 0;
                $score = $score < 5 ? $score+1 : $score;

                //todo  判断是否需要插入 UserFriendLevel
                //todo UserFriendLevel::;
            }
        } else {
            $data = ['user_id' => $userId, 'friend_id' => $friend_id];
            if ($raw['fromUserId'] == $userId ) {
                $uNum = 1;
            } else {
                $fNum =1;
            }
        }

        $dattt = array_merge($data ?? [], ['user_id_count'=>$uNum, 'friend_id_count'=>$fNum, 'talk_day'=>$today, 'score'=>$score]);
        dump($dattt);
        UserFriendTalkList::updateOrCreate(['id' => !empty($result['id']) ? $result['id'] : null], $dattt );



        // 计数用
        /*if ($raw['fromUserId'] == $arr[0]) {
            $memValue['fromUserId'] = 1;
        } else {
            $memValue['toUserId'] = 1;
        }*/
    }

    /*public function handle3()
    {
        $raw = $this->data;

        [$raw['fromUserId'], $raw['toUserId']];


        // 获取明天凌晨的时间戳
        $nextDay = strtotime(date('Y-m-d',strtotime('+1 day')));

        $arr     = [$raw['fromUserId'], $raw['toUserId']];
        sort($arr);
        $mKey    = 'ry_chat_friend_relation_status_'. implode('_', $arr);
        $mValue  = Redis::get($mKey);
        if (empty($mValue)) {
            $mValue = DB::table('f_users_friends')->where(['user_id'=>$raw['fromUserId'], 'friend_id'=>$raw['toUserId']])->first();
            if (empty($mValue)) return false;
            Redis::set($mKey, json_encode($mValue));
        }

        // 攒够20条可以创建 朋友关系
        $memKey  = 'ry_chat_friend_relation_'. implode('_', $arr);

        $value   = Redis::get($memKey);
        if (!empty($value)) {
            $value = json_decode($value, true);
            if (empty($value['status'])) {
                $value[$raw['fromUserId']] = !empty($value[$raw['fromUserId']]) ? $value[$raw['fromUserId']]+1 : 1;

                if (!empty($value[$raw['fromUserId']]) && !empty($value[$raw['toUserId']])) {
                    $count = array_sum([$value[$raw['fromUserId']], $value[$raw['toUserId']]]);
                    if ($count>=20) {
                        $value['status'] = 1;
                        Redis::set($memKey, json_encode($value, JSON_UNESCAPED_UNICODE));
                        //todo 符合创建关系条件  ----  等待用户发出创建关系邀请
                    }
                }
            }
        } else {
            $memValue[$raw['fromUserId']] = 1;
            $memValue['status']           = 0;

            Redis::set($memKey, json_encode($memValue, JSON_UNESCAPED_UNICODE));
        }
    }*/
}
