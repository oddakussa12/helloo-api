<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Custom\Queue\Bus\SqsFifoQueueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Repositories\Contracts\UserRepository;

class RyOnline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SqsFifoQueueable , SerializesModels;

    private $chinaNow;

    private $chinaDateTime;

    private $users;
    /**
     * @var int
     */
    private $time;
    /**
     * @var false|string
     */
    private $date;

    private $topics;

    public function __construct($users)
    {
        $this->chinaNow = Carbon::now('Asia/Shanghai');
        $this->chinaDateTime = $this->chinaNow->toDateTimeString();
        $this->time = $this->chinaNow->timestamp;
        $this->date = date('Y-m-d H:i:s' , $this->time);
        $this->users = $users;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $allUsers = $this->users;
        if(empty($allUsers)||isset($allUsers['offlineUsers'])||isset($allUsers['onlineUsers']))
        {
            return;
        }
        $users = (array)(collect($allUsers)->sortByDesc('time')->toArray());
        $users = assoc_unique($users , 'userid');
        $lastActivityTime = 'helloo:account:service:account-ry-last-activity-time';
        $key = 'helloo:account:service:account-random-im-set';
        $maleKey = 'helloo:account:service:account-random-male-im-set';
        $femaleKey = 'helloo:account:service:account-random-female-im-set';
        $bitKey = 'helloo:account:service:account-online-status-bit';
        $genderSortSetKey = 'helloo:account:service:account-gender-sort-set';
        $offlineUsers = collect($users)->whereIn('status', array(1 , 2));
        $offlineUserIds = $offlineUsers->pluck('userid')->all();
        $onlineUsers = collect($users)->where('status', 0);
        $onlineUserIds = $onlineUsers->pluck('userid')->all();
        if(!blank($onlineUserIds))
        {
            Redis::sadd($key , $onlineUserIds);
            $male = array();
            $female = array();
            array_walk($onlineUserIds , function ($user , $k) use ($genderSortSetKey , &$male , &$female){
                $gender = Redis::zscore($genderSortSetKey , $user);
                if($gender!==null)
                {
                    if($gender==0)
                    {
                        array_push($female , $user);
                    }else{
                        array_push($male , $user);
                    }
                }
            });
            !blank($male)&&Redis::sadd($maleKey , $male);
            !blank($female)&&Redis::sadd($femaleKey , $female);
        }
        $setVoiceKey = 'helloo:account:service:account-random-voice-set';
        $setMaleVoiceKey = 'helloo:account:service:account-random-male-voice-set';
        $setFemaleVoiceKey = 'helloo:account:service:account-random-female-voice-set';
        $setVideoKey = 'helloo:account:service:account-random-video-set';
        $setMaleVideoKey = 'helloo:account:service:account-random-male-video-set';
        $setFemaleVideoKey = 'helloo:account:service:account-random-female-video-set';
        if(!blank($offlineUserIds))
        {
            Redis::srem($key , $offlineUserIds);
            Redis::srem($maleKey , $offlineUserIds);
            Redis::srem($femaleKey , $offlineUserIds);
            Redis::srem($setVoiceKey , $offlineUserIds);
            Redis::srem($setVideoKey , $offlineUserIds);
            Redis::srem($setMaleVoiceKey , $offlineUserIds);
            Redis::srem($setMaleVideoKey , $offlineUserIds);
            Redis::srem($setFemaleVoiceKey , $offlineUserIds);
            Redis::srem($setFemaleVideoKey , $offlineUserIds);
        }
        $time = time();
        !blank($users)&&array_walk($users , function ($user , $k) use ($bitKey , $lastActivityTime , $time){
            $userId = intval($user['userid']);
            $status = $user['status'];
            $status = intval($status)>0?0:1;
            Redis::setBit($bitKey , $userId , $status);
            Redis::zadd($lastActivityTime , $time , $userId);
        });
        $chinaNow = $this->chinaDateTime;
        $key = 'helloo:account:service:account-au'.date('Ymd' , strtotime($chinaNow)); //20191125
        foreach ($allUsers as $user)
        {
            $userId = $user['userid'];
            $ipPort = $user['clientIp'];
            $p = strrpos($ipPort , ":");
            if($p==0)
            {
                $ip = $ipPort;
            }else{
                $ip = substr($ipPort , 0 , $p);
                $size = strlen($ip);
                if($size>15)
                {
                    $ip = $ipPort;
                }
            }
            $src = strval($user['os']);
            Redis::rpush($key."_op_list" , \json_encode(array(
                'visited_at'=>$time,
                'user_id'=>$userId,
                'referer'=>$src,
                'version'=>0,
                'ip'=>$ip
            )));//20201108
        }

    }

}
