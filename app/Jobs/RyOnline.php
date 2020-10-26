<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RyOnline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $chinaNow;

    private $chinaDatTime;

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
        $this->chinaDatTime = $this->chinaNow->toDateTimeString();
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
        $topics = $this->topics;
        $chinaNow = $this->chinaNow;
        $users = $this->users;
        $offlineUsers = $users['offlineUsers'];
        $onlineUsers = $users['onlineUsers'];
        $offlineUsers = array_keys($offlineUsers);
        $onlineUsers = array_keys($onlineUsers);
        if(!blank($onlineUsers))
        {
            foreach ($onlineUsers as $userId)
            {
                $kpop = 0;
                $anime = 0;
                $sad = 0;
                $music = 0;
                $games = 0;
                $sports = 0;
                $userTopicKey = 'user.'.$userId.'.topics';
                $userKey = "user.".strval($userId).'.data';
                $user = Redis::hgetAll($userKey);
                if(empty($user['user_name']))
                {
                    $user = collect(DB::table('users')->where('user_id' , $userId)->first())->toArray();
                    $user_name = $user['user_name'];
                    $user_nick_name = $user['user_nick_name'];
                    $user_age = isset($user['user_birthday'])?age($user['user_birthday']):0;
                    $user_gender = $user['user_gender'];
                    $user_country_id = $user['user_country_id'];
                    $user_avatar = $user['user_avatar'];
                    $user_created_at= strtotime($user['user_created_at']);
                }else{
                    $user_name = $user['user_name'];
                    $user_nick_name = $user['user_nick_name'];
                    $user_age = isset($user['user_birthday'])?age($user['user_birthday']):0;
                    $user_gender = $user['user_gender'];
                    $user_country_id = $user['user_country_id'];
                    $user_avatar = $user['user_avatar'];
                    $user_created_at= $user['user_created_at'];
                }
                $user = DB::table('ry_online_users')->where('user_id' , $userId)->first();
                if(blank($user))
                {
                    foreach ($topics as $topic=>$data)
                    {
                        foreach ($data as $t)
                        {
                            $t = strval($t);
                            if(Redis::zrank($userTopicKey , $t)!==null)
                            {
                                $$topic = 1;
                                break;
                            }
                        }
                    }

                    \DB::table('ry_online_users')->insert(
                        array(
                            'user_id'=>$userId,
                            'user_name'=>$user_name??'guest',
                            'user_nick_name'=>$user_nick_name??'guest',
                            'user_age'=>$user_age,
                            'user_gender'=>$user_gender??0,
                            'user_country_id'=>$user_country_id??246,
                            'user_avatar'=>$user_avatar??'default_avatar.jpg',
                            'kpop' => $kpop,
                            'anime' => $anime,
                            'sad' => $sad,
                            'music' => $music,
                            'games' => $games,
                            'sports' => $sports,
                            'created_at'=>$this->time,
                            'updated_at'=>$this->time,
                            'user_created_at'=>$user_created_at??$this->date,
                        )
                    );
                }
            }
        }
        if(!blank($offlineUsers))
        {
            $userIds = join(',' , $offlineUsers);
            $userIds = rtrim($userIds , ',');
            \DB::statement("delete from `f_ry_online_users` where user_id in ({$userIds});");
        };
        $allUsers = array_unique(array_merge($onlineUsers , $offlineUsers));
        $key = 'au'.date('Ymd' , strtotime($chinaNow)); //20191125
        foreach ($allUsers as $userId)
        {
            if(!Redis::setbit($key , $userId , 1))
            {
                $view = DB::table('views_logs')->where('user_id' , $userId)->orderBy('id' , 'DESC')->first();
                if(empty($view)||Carbon::parse($view->created_at , 'Asia/Shanghai')->endOfDay()->timestamp<$chinaNow->endOfDay()->timestamp)
                {
                    DB::table('views_logs')->insert(array(
                        'user_id'=>$userId,
                        'ip'=>'127.0.0.1',
                        'referer'=>'ry',
                        'created_at'=>$this->chinaDatTime
                    ));
                }
            }
            Redis::rpush($key."_op_list" , strval($userId).'.'.strval($this->time));//20201017
        }
    }

}
