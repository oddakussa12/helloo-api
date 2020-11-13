<?php

namespace App\Jobs;

use App\Custom\Constant\Constant;
use App\Models\User;
use App\Models\UserFriendLevel;
use App\Models\UserFriendLevelHistory;
use App\Models\UserFriendTalk;
use App\Models\UserFriendTalkList;
use App\Repositories\Contracts\UserRepository;
use App\Resources\UserCollection;
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
     * @return mixed
     * 创建特殊好友关系
     */
    public function handle()
    {

    }

    /**
     * @param $userId
     * @param $friendId
     * @param $objectName
     * @param $data
     *
     * 发送系统消息给融云
     * @param string $userAgent
     */
    public static function sendMsgToRyBySystem($userId, $friendId, $objectName, $data , $userAgent='mobile')
    {
        $user = app(UserRepository::class)->findByUserId($userId);
        if (empty($user)) {
            return;
        }

        $content = [
            'data'=>$data,
            'userInfo' => collect($user)->toArray()
        ];

        // 融云推送 聊天
        if (Constant::QUEUE_PUSH_TYPE=='redis') {
            RySystem::dispatch($userId, $friendId, $objectName, $content, $userAgent)->onQueue(Constant::QUEUE_RY_CHAT_FRIEND);
        } else {
            RySystem::dispatch($userId, $friendId, $objectName, $content, $userAgent)->onConnection('sqs')->onQueue(Constant::QUEUE_RY_CHAT_FRIEND);
        }
    }


    /**
     * @param $userId
     * @param $friendId
     * @param $objectName
     * @param $data
     *
     * 发送自定义消息给融云
     * @param string $userAgent
     */
    public static function sendMsgToRyByPerson($userId, $friendId, $objectName, $data, $userAgent='mobile')
    {
        // 融云推送 聊天
        if (Constant::QUEUE_PUSH_TYPE=='redis') {
            Friend::dispatch($userId, $friendId, $objectName, $data, $userAgent)->onQueue(Constant::QUEUE_RY_CHAT_FRIEND);
        } else {
            Friend::dispatch($userId, $friendId, $objectName, $data, $userAgent)->onConnection('sqs')->onQueue(Constant::QUEUE_RY_CHAT_FRIEND);
        }
    }


}
