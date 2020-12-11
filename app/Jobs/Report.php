<?php

namespace App\Jobs;

use App\Custom\RedisList;
use Illuminate\Bus\Queueable;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Report implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $auth;
    private $reportedId;
    private $reportedType;

    public function __construct($auth , $reportedId , $type)
    {
        $this->auth = collect(new UserCollection($auth))->only(['user_id' , 'user_nick_name' , 'user_avatar_link'])->toArray();
        $this->reportedId = $reportedId;
        $this->reportedType = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $count = intval(DB::table('reports')->where('reported_id' , $this->reportedId)->count(DB::raw("distinct(user_id)")));
        $key = 'helloo:account:service:account-reported-sort-set';
        $reportedCount = intval(Redis::zscore($key , $this->reportedId));
        if($reportedCount!=$count)
        {
            $redis = new RedisList();
            $lockedKey = 'helloo:account:service:account-reported-locked:'.$this->reportedId;
            $redis->releaseLock($lockedKey);
            if($count<=2&&$count>0)
            {
                $redis->tryGetLock($lockedKey , 1 , 3600000);
            }else{
                $redis->tryGetLock($lockedKey , 1 , 86400000);
            }
            Redis::zadd($key , $count , $this->reportedId);
            $level = $count>3?3:$count;
            $content = array(
                'senderId'   => 'System',
                'targetId'   => $this->reportedId,
                "objectName" => "Helloo:UserReported",
                'content'    => array(
                    'reportedLevel'=>$level,
                    'reportedType'=>$this->reportedType,
                    'content'=>'You have been reported',
                    'whistleblower'=> $this->auth
                )
            );
            $result = app('rcloud')->getMessage()->System()->send($content);
            Log::error($this->auth);
            Log::error($content);
            Log::error(\json_encode($result , JSON_UNESCAPED_UNICODE));
        }
    }

}
