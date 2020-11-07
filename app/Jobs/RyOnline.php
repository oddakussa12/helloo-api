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
        $offlineUsers = $users['offlineUsers'];
        $onlineUsers = $users['onlineUsers'];
        if(!blank($onlineUsers))
        {
            foreach ($onlineUsers as $u)
            {
                $userId = $u['userid'];
                $userKey = "user.".strval($userId).'.data';
                $user = Redis::hgetAll($userKey);
                if(empty($user['user_name']))
                {
                    $user = collect(DB::table('users')->where('user_id' , $userId)->first())->toArray();
                    $user_created_at= strtotime($user['user_created_at']);
                }else{
                    $user_created_at= $user['user_created_at'];
                }
                $user_name = $user['user_name'];
                $user_nick_name = $user['user_nick_name'];
                $user_age = isset($user['user_birthday'])?age($user['user_birthday']):0;
                $user_gender = $user['user_gender'];
                $user_country_id = $user['user_country_id'];
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
            !blank($online)&&DB::table('ry_online_users')->insert($online);
        }
        if(!blank($offlineUsers))
        {
            $offlineUserIds = collect($offlineUsers)->pluck('userid')->all();
            $userIds = join(',' , $offlineUserIds);
            $userIds = rtrim($userIds , ',');
            DB::statement("delete from `f_ry_online_users` where user_id in ({$userIds});");
        };
        $allUsers = array_merge($onlineUsers , $offlineUsers);
        $key = 'au'.date('Ymd' , strtotime($chinaNow)); //20191125
        $log = array();
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
            if(!Redis::setbit($key , $userId , 1))
            {
                $view = DB::table('views_logs')->where('user_id' , $userId)->orderBy('id' , 'DESC')->first();
                if(empty($view)||Carbon::parse($view->created_at , 'Asia/Shanghai')->endOfDay()->timestamp<$chinaNow->endOfDay()->timestamp)
                {
                    array_push($log , array(
                        'user_id'=>$userId,
                        'ip'=>$ip,
                        'referer'=>$referer,
                        'created_at'=>$this->chinaDateTime
                    ));
                }
            }
            Redis::rpush($key."_op_list" , strval($userId).'.'.strval($this->time));//20201017
        }
        !blank($log)&&DB::table('views_logs')->insert($log);
    }

}
