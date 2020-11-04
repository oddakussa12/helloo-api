<?php

namespace App\Jobs;

use App\Custom\Constant\Constant;
use App\Models\UserFriend;
use App\Models\UserFriendSignIn;
use App\Models\UserVisitLog;
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
 * 个人主页访客记录
 */
class UserVisit implements ShouldQueue
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
     * 个人主页访客记录
     */
    public function handle()
    {

        $raw = $this->data;

        if (empty($raw)) {
            Log::error(__FILE__. ' message:::::data is  empty  data is  empty');
        }

        Redis::hincrby(config('redis-key.user.user_visit'), $raw['toUserId'] , 1);

        // 入库操作
        UserVisitLog::create(['user_id'=>$raw['fromUserId'],'friend_id'=>$raw['toUserId']]);
    }
}
