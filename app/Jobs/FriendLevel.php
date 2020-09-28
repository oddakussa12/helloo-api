<?php

namespace App\Jobs;

use App\Custom\Constant\Constant;
use App\Models\UserFriendLevel;
use App\Models\UserFriendLevelHistory;
use App\Models\UserFriendTalk;
use App\Models\UserFriendTalkList;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
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
class FriendLevel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
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
        list($userId, $friendId) = $arr = self::sortId($userId, $friendId);

        if ($cache) {
            $memKey   = Constant::RY_CHAT_FRIEND_RELATIONSHIP.$userId.'_'.$friendId;
            $memValue = Redis::get($memKey);
            if (empty($memValue)) {
                $result = UserFriendLevel::where(['user_id'=>$userId, 'friend_id'=>$friendId, 'is_delete'=>0, 'status'=>1])->first();
                if (empty($result)) return false;
                Redis::set($memKey, json_encode($result, JSON_UNESCAPED_UNICODE));
            } else {
                return json_decode($memValue, true);
            }
        } else {
            return UserFriendLevel::where(['user_id'=>$userId, 'friend_id'=>$friendId, 'is_delete'=>0, 'status'=>1])->first();
        }
    }


    public static function sortId($user_id, $friendId)
    {
        $arr = [$user_id, $friendId];
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
            Log::info('不是好友');
            return false;
        }
        $isFriendRelation = $this->isFriendRelation($raw['fromUserId'], $raw['toUserId'], false);

        list($userId, $friendId) = $arr = FriendSignIn::sortId($raw['fromUserId'], $raw['toUserId']);

        // 插入总表
        $total = UserFriendTalk::where(['user_id'=>$userId, 'friend_id'=>$friendId, 'is_delete'=>0])->first();
        if (!empty($total)) {
            $uNum  = $total['user_id']   == $raw['fromUserId'] ? $total['user_id_count']+1   : $total['user_id_count'];
            $fNum  = $total['friend_id'] == $raw['fromUserId'] ? $total['friend_id_count']+1 : $total['friend_id_count'];
            UserFriendTalk::updateOrCreate(['id' => $total['id']],
                ['user_id'=>$userId, 'friend_id'=>$friendId, 'user_id_count'=>$uNum ?? 0, 'friend_id_count'=>$fNum ?? 0]);
        }

        $today  = date('Ymd');
        $result = UserFriendTalkList::where(['user_id'=>$userId, 'friend_id'=>$friendId, 'is_delete'=>0])->first();
        $uNum   = $fNum = $score = 0;

        // 当聊天记录条数为空时，初始化
        if (empty($result) || empty($total)) {
            $raw['fromUserId'] == $userId ? $uNum = 1 : $fNum =1;
            $data = ['user_id' => $userId, 'friend_id' => $friendId, 'user_id_count'=>$uNum,
                'friend_id_count'=>$fNum, 'talk_day'=>$today, 'heart_count'=>1];

            empty($result) && UserFriendTalkList::updateOrCreate(['id' => null], $data);
            empty($total)  && UserFriendTalk::updateOrCreate(['id' => null], $data);
        }

        if (!empty($result)) {
            // 当特殊好友关系不存在，且有一颗心时，直接返回
            if (empty($isFriendRelation) && $result['score']>=1) {
                dump('当特殊好友关系不存在，且有一颗心');
                return true;
            }
            $uNum  = $result['user_id']   == $raw['fromUserId'] ? $result['user_id_count']+1   : $result['user_id_count'];
            $fNum  = $result['friend_id'] == $raw['fromUserId'] ? $result['friend_id_count']+1 : $result['friend_id_count'];
            $score = $result['talk_day']  == $today ? $result['score'] : 0;
            $count = array_sum([$uNum, $fNum]);


            if (!empty($uNum) && !empty($fNum) && $count>=Constant::CHAT_SUM_STAR) {

                $score = $score <= $star ? $score+1 : $score;

                //星数>5时，直接返回
                if ($score>$star) {
                    dump('心大于5，结束。');
                    return false;
                }

                if (!empty($isFriendRelation) && $score<=$star) {

                    // 插入升级历史表
                    $date = ['user_id_count'=>$uNum, 'friend_id_count'=>$fNum, 'talk_day'=>$today, 'score'=>$score];
                    if (!empty($isFriendRelation) && $score<=$star) {
                        $date = array_merge($result->toArray(), $date);
                        unset($date['id']);
                        UserFriendLevelHistory::create($date);
                    }

                    $uNum  = $fNum = 0;


                    // 插入好友关系等级表
                    UserFriendLevel::updateOrCreate(['id'=>$isFriendRelation['id']],
                        ['heart_count'=>$isFriendRelation['heart_count']+1]
                    );

                    // 升级之后，清空情侣首页缓存
                    Redis::del(Constant::FRIEND_RELATIONSHIP_MAIN.$userId.'_'.$friendId);
                    Redis::del(Constant::FRIEND_RELATIONSHIP_HOME_TOP.$userId);

                   // $this->sendMsgToRongYun($userId, $friendId, 'Yooul:AffinityFriendLevel', $isFriendRelation['relationship_id'], $score);
                   // $this->sendMsgToRongYun($friendId, $userId, 'Yooul:AffinityFriendLevel', $isFriendRelation['relationship_id'], $score);


                }

                if (empty($isFriendRelation)) {
                    $score = $isFriendRelation ? $score : 1;
                    $uNum  = $fNum = 0;
                }

                // 发送升级请求给双方 融云
                $ryData = [
                    'heart_count'     => $isFriendRelation['heart_count']+1,
                    'relationship_id' => $isFriendRelation['relationship_id'],
                ];

                $this->sendMsgToRongYun($userId, $friendId, 'RC:CmdMsg', $ryData);
                $this->sendMsgToRongYun($friendId, $userId, 'RC:CmdMsg', $ryData);

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

    public function sendMsgToRongYun($userId, $friendId, $objectName, $data)
    {
        $user = Redis::hgetall('user.'.$userId.'.data');
        // 融云推送 聊天
       $result = $this->dispatch((new RySystem($userId, $friendId, $objectName, [
            'name'     => 'HEART_UPGRADE',
            'data'     => $data,
            'userInfo' => $user
        ]))->onQueue(Constant::QUEUE_RY_CHAT_FRIEND));
        dump($result);
    }


    /*public function sendMsgToRongYun($userId, $friendId, $objectName, $relationship_id, $score)
    {
        $user = Redis::hgetall('user.'.$userId.'.data');
        // 融云推送 聊天
        $this->dispatch((new Friend($userId, $friendId, $objectName, [
            'content'         => 'friend request',
            'score'           => $score,
            'relationship_id' => $relationship_id,
            'user'            => $user
        ]))->onQueue(Constant::QUEUE_RY_CHAT_FRIEND));
    }*/

}
