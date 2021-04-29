<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class LastThreeDayActiveUser extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'last:three';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Last Three';

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
        $perPage = 1000;
        $key = 'helloo:account:service:account-ry-last-three-day-activity-user-'.date('Y-m-d');
        Redis::del($key);
        $lastActivityTime = 'helloo:account:service:account-ry-last-activity-time';
        $end = Carbon::now('Asia/Shanghai')->subDays(3)->startOfDay()->timestamp;
        $yesterdayStart = Carbon::yesterday('Asia/Shanghai')->startOfDay()->subHours(8)->toDateTimeString();
        $yesterdayEnd = Carbon::yesterday('Asia/Shanghai')->endOfDay()->subHours(8)->toDateTimeString();
        $yesterdayChinaStart = Carbon::yesterday('Asia/Shanghai')->startOfDay()->timestamp;
        $yesterdayChinaEnd = Carbon::yesterday('Asia/Shanghai')->endOfDay()->timestamp;
        $count = Redis::zcard($lastActivityTime);
        $num = ceil($count/$perPage);
        for($i=1;$i<=$num;$i++) {
            $offset = ($i - 1) * $perPage;
            $options = array('withScores' => true, 'limit' => array($offset, $perPage));
            $userIds = array_keys(Redis::ZREVRANGEBYSCORE($lastActivityTime, '+inf', $end, $options));
            !empty($userIds)&&Redis::sadd($key , $userIds);
        }
//        $userIds = array();
//        while ($userId = Redis::spop($key))
//        {
//            array_push($userIds , $userId);
//            if(count($userIds)>=10)
//            {
//                $data = array();
//                $counts = collect(DB::table('users_friends')
//                    ->whereIn('user_id' , $userIds)
//                    ->where('created_at' , '>=' , $yesterdayChinaStart)
//                    ->where('created_at' , '<=' , $yesterdayChinaEnd)
//                    ->groupBy('user_id')
//                    ->count());
//                $users = DB::table('users')->whereIn('user_id' , $userIds)->get();
//                foreach ($userIds as $u)
//                {
//                    $count = $counts->where('user_id' , $u)->first();
//                    if(!empty($count))
//                    {
//                        dump($counts);
//                        dump($count);
//                    }
//                    $user = $users->where('user_id' , $u)->first();
//                    array_push($data , array(
//                        'user_id'=>$user->user_id,
//                        'user_name'=>$user->user_name,
//                        'user_nick_name'=>$user->user_nick_name,
//                        'count'=>empty($count)?0:$count,
//                        'user_created_at'=>$user->user_created_at,
//                    ));
//                }
//                $userIds =  array();
//            }
//        }
//        Redis::del($key);
    }
}
