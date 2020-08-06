<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;


class AutoUpdateOnlineUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:update_online_user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto Update Online User';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $currentMinute = date('i');
        $index = floor($currentMinute/5);
        $index = $index<=0?11:($index-1);
        $dynamicKey = config('redis-key.user.ry_update_online_status')."_".strval($index);
        $perPage = 100;
        if(Redis::exists($dynamicKey))
        {
            $count = Redis::zcard($dynamicKey);
            $num = ceil($count/$perPage);
            for($i=1;$i<=$num;$i++)
            {
                $offset = ($i-1)*$perPage;
                $options = array('withScores'=>true , 'limit'=>array($offset , $perPage));
                $userIds = Redis::ZREVRANGEBYSCORE($dynamicKey,'+inf','-inf' ,  $options);
                $onlineUsers = array_where($userIds, function ($value, $key) {
                    return intval($value)>0;
                });
                $onlineUsers = array_keys($onlineUsers);

                $offlineUsers = array_where($userIds, function ($value, $key) {
                    return intval($value)===0;
                });
                $offlineUsers = array_keys($offlineUsers);

                if(!blank($onlineUsers))
                {
                    foreach ($onlineUsers as $userId)
                    {
                        $userKey = "user.".strval($userId).'.data';
                        $user = Redis::hgetAll($userKey);
                        $user_name = $user['user_name'];
                        $user_nick_name = $user['user_nick_name'];
//                        $user_age = $user['user_age'];
                        $user_gender = $user['user_gender'];
                        $user_country_id = $user['user_country_id'];
                        $user_avatar = $user['user_avatar'];
                        $user_created_at= $user['user_created_at'];
                        $sql = <<<DOC
INSERT INTO `f_ry_online_users` ( `user_id`, `user_name`, `user_nick_name`, `user_gender`, `user_country_id`, `user_avatar`, `user_created_at`) SELECT ?,?,?,?,?,?,?  FROM DUAL WHERE NOT EXISTS ( SELECT `user_id` FROM `f_ry_online_users` WHERE `user_id` = ?)
DOC;
                        \DB::statement($sql , array(
                            $userId,
                            $user_name,
                            $user_nick_name,
                            $user_gender,
                            $user_country_id,
                            $user_avatar,
                            $user_created_at,
                            $userId
                        ));
                    }
                }
                if(!blank($offlineUsers))
                {
                    $offlineUsers = join(',' , $offlineUsers);
                    $offlineUsers = rtrim($offlineUsers , ',');
                    \DB::statement("delete from `f_ry_online_users` where user_id in ({$offlineUsers});");
                }
            }
        }
    }
}
