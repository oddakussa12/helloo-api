<?php

namespace App\Jobs;

use App\Custom\Constant\Constant;
use App\Models\UserFriendLevel;
use App\Models\UserFriendLevelHistory;
use App\Models\UserFriendRelationShipRule;
use App\Models\UserFriendTalk;
use App\Models\UserFriendTalkList;
use GPBMetadata\Google\Api\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use function foo\func;

/**
 * Class FriendSignIn
 * @package App\Jobs
 * 朋友每日签到
 */
class FriendLevel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
        //$this->handle();
    }

    /**
     * @param $userId
     * @param $friendId
     * @param bool $cache
     * @return bool
     * 是否存在特殊好友关系（情侣 基友等）
     */
    public static function isFriendRelation($userId, $friendId, $cache=true)
    {
        $arr     = self::sortId($userId, $friendId);
        list($userId, $friendId) = $arr;
        if ($cache) {
            $memKey   = Constant::RY_CHAT_FRIEND_RELATIONSHIP. explode('_', $arr);
            $memValue = Redis::get($memKey);
            if (empty($memValue)) {
                $result = UserFriendLevel::where(['user_id'=>$userId, 'friend_id'=>$friendId, 'is_delete'=>0, 'status'=>1])->first();
                if (empty($result)) return false;
                Redis::set($memKey, 1);
            } else {
                return true;
            }
        } else {
            return UserFriendLevel::where(['user_id'=>$userId, 'friend_id'=>$friendId, 'is_delete'=>0])->first();
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
    public function handle()
    {
        $raw      = $this->data;
        $star     = Constant::DAY_UPPER_STAR;
        $isFriend = FriendSignIn::isFriend($raw['fromUserId'], $raw['toUserId']);
        if (empty($isFriend)) {
            return false;
        }
        $isFriendRelation = $this->isFriendRelation($raw['fromUserId'], $raw['toUserId'], false);

        list($userId, $friend_id) = $arr = FriendSignIn::sortId($raw['fromUserId'], $raw['toUserId']);

        // 插入总表
        $total = UserFriendTalk::where(['user_id'=>$userId, 'friend_id'=>$friend_id, 'is_delete'=>0])->first();
        if (!empty($total)) {
            $uNum  = $total['user_id']   == $raw['fromUserId'] ? $total['user_id_count']+1   : $total['user_id_count'];
            $fNum  = $total['friend_id'] == $raw['fromUserId'] ? $total['friend_id_count']+1 : $total['friend_id_count'];
            UserFriendTalk::updateOrCreate(['id' => $total['id']],
                ['user_id'=>$userId, 'friend_id'=>$friend_id, 'user_id_count'=>$uNum ?? 0, 'friend_id_count'=>$fNum ?? 0]);
        }

        $today  = date('Ymd');
        $result = UserFriendTalkList::where(['user_id'=>$userId, 'friend_id'=>$friend_id, 'is_delete'=>0])->first();
        $uNum   = $fNum = $score = 0;

        // 当聊天记录条数为空时，初始化
        if (empty($result) || empty($total)) {
            $raw['fromUserId'] == $userId ? $uNum = 1 : $fNum =1;
            $data = ['user_id' => $userId, 'friend_id' => $friend_id, 'user_id_count'=>$uNum,
                'friend_id_count'=>$fNum, 'talk_day'=>$today, 'heart_count'=>$score];

            empty($result) && UserFriendTalkList::updateOrCreate(['id' => null], $data);
            empty($total)  && UserFriendTalk::updateOrCreate(['id' => null], $data);
        }

        if (!empty($result)) {
            $uNum  = $result['user_id']   == $raw['fromUserId'] ? $result['user_id_count']+1   : $result['user_id_count'];
            $fNum  = $result['friend_id'] == $raw['fromUserId'] ? $result['friend_id_count']+1 : $result['friend_id_count'];
            $score = $result['talk_day']  == $today ? $result['heart_count'] : 0;
            $count = array_sum([$uNum, $fNum]);

            if (!empty($uNum) && !empty($fNum) && $count>=Constant::CHAT_SUM_STAR) {
                // 当特殊好友关系不存在
                if (empty($isFriendRelation)) return false;

                //星数>5时，直接返回
                if ($score>$star) return false;

                $date = ['user_id_count'=>$uNum, 'friend_id_count'=>$fNum, 'talk_day'=>$today, 'score'=>$score];
                UserFriendTalkList::updateOrCreate(['id' => $result['id']], $date);


                if (!empty($isFriendRelation) && $score<=$star) {
                    // 插入升级历史表
                    if (!empty($isFriendRelation) && $score<=$star) {
                        $date = array_merge($result->toArray(), $date);
                        unset($date['id']);
                        UserFriendLevelHistory::create($date);
                    }

                    $uNum  = $fNum = 0;
                    $score = $score < $star ? $score+1 : $score;

                    /*$rule  = UserFriendRelationShipRule::select('id', 'name', 'score', 'desc')
                        ->where(['relationship_id'=>$isFriendRelation['relationship_id'], 'is_delete'=>0])
                        ->orderBy('score', 'DESC')->get()->toArray();

                    $ruleId = array_filter($rule, function ($value) use ($score) {
                        if ($score>=$value['score']) {return $value['id'];}
                    });*/

                    // 插入好友关系等级表
                    UserFriendLevel::updateOrCreate(['id'=>$isFriendRelation['id']],
                        ['heart_count'=>$isFriendRelation['heart_count']+1]
                    );

                    // 发送升级请求给双方 融云
                    $this->sendMsgToRongYun($userId, $friend_id, 'Yooul:AffinityFriendLevel', $score);
                    $this->sendMsgToRongYun($friend_id, $userId, 'Yooul:AffinityFriendLevel', $score);
                }


            }
            //else{
            // 未加特殊关系或未满20条时  叠加聊天条数
            $data = ['user_id_count'=>$uNum, 'friend_id_count'=>$fNum, 'talk_day'=>$today, 'score'=>$score];
            dump($data);
            UserFriendTalkList::updateOrCreate(['id' => $result['id']], $data);
            return true;
            //}
        }
    }

    public function sendMsgToRongYun($userId, $friendId, $objectName, $score)
    {
        // 融云推送 聊天
        $this->dispatch((new Friend($userId, $friendId, $objectName, [
            'content' => 'friend request',
            'score'   => $score,
            // 'user'    => $user
        ]))->onQueue(Constant::RY_CHAT_FRIEND));
    }

}
