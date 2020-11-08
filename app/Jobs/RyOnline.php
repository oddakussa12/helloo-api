<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
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
        $this->topics = config('topics');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $chinaNow = $this->chinaNow;
        $users = $this->users;
        $online = array();
        $male = array();
        $female = array();
        $offlineUsers = $users['offlineUsers'];
        $onlineUsers = $users['onlineUsers'];
        $maleKey = 'helloo:account:service:account-ry-online-male-status';
        $femaleKey = 'helloo:account:service:account-ry-online-female-status';
        $userRepository = app(UserRepository::class);
        if(!blank($onlineUsers))
        {
            foreach ($onlineUsers as $u)
            {
                $userId = $u['userid'];
                $user = $userRepository->findByUserId($userId);
                if(blank($user))
                {
                    continue;
                }
                $user_nick_name = $user['user_nick_name'];
                $user_age = isset($user['user_birthday'])?age($user['user_birthday']):0;
                $user_gender = $user['user_gender'];
                if($user_gender==0)
                {
                    array_push($female , $userId);
                }else{
                    array_push($male , $userId);
                }
                $user_avatar = $user['user_avatar'];
                $user = DB::table('ry_online_users')->where('user_id' , $userId)->first();
                if(blank($user))
                {
                    array_push($online , array(
                        'user_id'=>$userId,
                        'user_nick_name'=>$user_nick_name??'guest',
                        'user_age'=>$user_age,
                        'user_gender'=>$user_gender??0,
                        'user_avatar'=>$user_avatar??'default_avatar.jpg',
                        'created_at'=>$this->time,
                        'updated_at'=>$this->time,
                        'user_created_at'=>$user_created_at??$this->date,
                    ));

                }
            }
            !blank($male)&&Redis::sadd($maleKey , $male);
            !blank($female)&&Redis::sadd($femaleKey , $female);
            !blank($online)&&DB::table('ry_online_users')->insert($online);
        }
        if(!blank($offlineUsers))
        {
            $offlineUserIds = collect($offlineUsers)->pluck('userid')->all();
            Redis::srem($maleKey , $offlineUserIds);
            Redis::srem($femaleKey , $offlineUserIds);
            $userIds = join(',' , $offlineUserIds);
            $userIds = rtrim($userIds , ',');
            DB::statement("delete from `f_ry_online_users` where user_id in ({$userIds});");
        };
        $allUsers = array_merge($onlineUsers , $offlineUsers);
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
            $referer = strval($user['os']);
            Redis::rpush($key."_op_list" , \json_encode(array(
                'user_id'=>$userId,
                'ip'=>$ip,
                'referer'=>$referer,
                'referer'=>$referer,
                'created_at'=>$this->chinaDateTime
            )));//20201108
        }

    }

}
