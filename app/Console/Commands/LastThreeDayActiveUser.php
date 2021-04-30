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
        $today = Carbon::now('Asia/Shanghai')->toDateString();
        $perPage = 1000;
        $key = 'helloo:account:service:account-ry-last-three-day-activity-user-'.$today;
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
        $userIds = array();
        while ($userId = Redis::spop($key))
        {
            array_push($userIds , $userId);
            if(count($userIds)>=10)
            {
                $data = array();
                $counts = collect(DB::table('users_friends')
                    ->select('user_id', DB::raw('count(friend_id) as total'))
                    ->whereIn('user_id' , $userIds)
                    ->where('created_at' , '>=' , $yesterdayChinaStart)
                    ->where('created_at' , '<=' , $yesterdayChinaEnd)
                    ->groupBy('user_id')
                    ->get());
                $users = DB::table('users')->whereIn('user_id' , $userIds)->get();
                foreach ($userIds as $u)
                {
                    $count = $counts->where('user_id' , $u)->first();
                    if(!empty($count)&&$count->total>0)
                    {
                        $user = $users->where('user_id' , $u)->first();
                        array_push($data , array(
                            'user_id'=>$user->user_id,
                            'user_name'=>$user->user_name,
                            'user_nick_name'=>$user->user_nick_name,
                            'count'=>empty($count)?0:$count->total,
                            'user_created_at'=>$user->user_created_at,
                        ));
                    }
                }
                if(!empty($data))
                {
                    foreach ($data as $i=>$d)
                    {
                        $userFriends = DB::table('users_friends')->where('user_id' , $d['user_id'])->where('created_at' , '>=' , $yesterdayChinaStart)
                            ->where('created_at' , '<=' , $yesterdayChinaEnd)->get()->map(function ($value) {
                                return (array)$value;
                            })->toArray();
                        $friendIds = collect($userFriends)->pluck('friend_id')->toArray();
                        $friends = collect(DB::table('users')->whereIn('user_id' , $friendIds)->where('user_created_at' , '>=' , $yesterdayStart)
                            ->where('user_created_at' , '<=' , $yesterdayEnd)->get()->map(function ($value) {
                                return (array)$value;
                            }))->toArray();
                        $todayFriend = collect($friends)->map(function($friend){
                            return array('user_id'=>$friend['user_id'] , 'user_name'=>$friend['user_name'] , 'user_nick_name'=>$friend['user_nick_name'] , 'user_created_at'=>$friend['user_created_at']);
                        });
                        $c = $todayFriend->count();
                        $json = implode(';' , $todayFriend->pluck('user_id')->toArray())."||".implode(';' , $todayFriend->pluck('user_name')->toArray())."||".implode(';' , $todayFriend->pluck('user_nick_name')->toArray());
                        file_put_contents('/home/wwwroot/api.helloo.mantouhealth.com/storage/app/tmp/count.csv' , $d['user_id'].','.$d['user_name'].','.$d['user_nick_name'].','.$d['count'].','.$c.','.$json.','.$d['user_created_at'].PHP_EOL , FILE_APPEND);
                    }
                }
                $data = array();
                $userIds =  array();
            }
        }
        if(!empty($userIds))
        {
            $data = array();
            $counts = collect(DB::table('users_friends')
                ->select('user_id', DB::raw('count(friend_id) as total'))
                ->whereIn('user_id' , $userIds)
                ->where('created_at' , '>=' , $yesterdayChinaStart)
                ->where('created_at' , '<=' , $yesterdayChinaEnd)
                ->groupBy('user_id')
                ->get());
            $users = DB::table('users')->whereIn('user_id' , $userIds)->get();
            foreach ($userIds as $u)
            {
                $count = $counts->where('user_id' , $u)->first();
                if(!empty($count)&&$count->total>0)
                {
                    $user = $users->where('user_id' , $u)->first();
                    array_push($data , array(
                        'user_id'=>$user->user_id,
                        'user_name'=>$user->user_name,
                        'user_nick_name'=>$user->user_nick_name,
                        'count'=>empty($count)?0:$count->total,
                        'user_created_at'=>$user->user_created_at,
                    ));
                }
            }
            if(!empty($data))
            {
                foreach ($data as $i=>$d)
                {
                    $userFriends = DB::table('users_friends')->where('user_id' , $d['user_id'])->where('created_at' , '>=' , $yesterdayChinaStart)
                        ->where('created_at' , '<=' , $yesterdayChinaEnd)->get()->map(function ($value) {
                            return (array)$value;
                        })->toArray();
                    $friendIds = collect($userFriends)->pluck('friend_id')->toArray();
                    $friends = collect(DB::table('users')->whereIn('user_id' , $friendIds)->where('user_created_at' , '>=' , $yesterdayStart)
                        ->where('user_created_at' , '<=' , $yesterdayEnd)->get()->map(function ($value) {
                            return (array)$value;
                        }))->toArray();
                    $todayFriend = collect($friends)->map(function($friend){
                        return array('user_id'=>$friend['user_id'] , 'user_name'=>$friend['user_name'] , 'user_nick_name'=>$friend['user_nick_name'] , 'user_created_at'=>$friend['user_created_at']);
                    });
                    $c = $todayFriend->count();
                    $json = implode(';' , $todayFriend->pluck('user_id')->toArray())."||".implode(';' , $todayFriend->pluck('user_name')->toArray())."||".implode(';' , $todayFriend->pluck('user_nick_name')->toArray());
                    file_put_contents(storage_path('app/tmp/'.$today.'count.csv') , $d['user_id'].','.$d['user_name'].','.$d['user_nick_name'].','.$d['count'].','.$c.','.$json.','.$d['user_created_at'].PHP_EOL , FILE_APPEND);
                }
            }
        }
        Redis::del($key);
    }
}
