<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ActiveUser extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'active:user {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Active User';

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
        $yesterday = Carbon::yesterday('Asia/Shanghai');
        $index = $yesterday->format('Ym');
        $created_at =  $yesterday->toDateString();
        $today = Carbon::yesterday('Asia/Shanghai')->toDateString();
        $todayTime = Carbon::now('Asia/Shanghai')->toDateTimeString();
        $yesterdayChinaStart = Carbon::yesterday('Asia/Shanghai')->startOfDay()->timestamp;
        $yesterdayChinaEnd = Carbon::yesterday('Asia/Shanghai')->endOfDay()->timestamp;
        $yesterdayStart = Carbon::yesterday('Asia/Shanghai')->startOfDay()->subHours(8)->toDateTimeString();
        $yesterdayEnd = Carbon::yesterday('Asia/Shanghai')->endOfDay()->subHours(8)->toDateTimeString();
        $table = "visit_logs_".$index;
        DB::table($table)->where('created_at' , $created_at)->select('user_id')->orderByDesc('visited_at')->distinct()->chunk(500 , function($users) use ($yesterdayChinaStart , $yesterdayChinaEnd , $yesterdayStart ,$yesterdayEnd , $todayTime , $today){
            $data = array();
            $userIds = $users->pluck('user_id')->toArray();
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
                $user = $users->where('user_id' , $u)->first();
                array_push($data , array(
                    'user_id'=>$user->user_id,
                    'user_name'=>$user->user_name,
                    'user_nick_name'=>$user->user_nick_name,
                    'friend'=>empty($count)?0:$count->total,
                    'user_created_at'=>$user->user_created_at,
                ));
            }
            if(!empty($data))
            {
                $uIds = collect($data)->pluck('user_id')->toArray();
                $phones = collect(DB::table('users_phones')->whereIn('user_id' , $uIds)->get()->map(function ($value) {
                    return (array)$value;
                })->toArray());
                foreach ($data as $i=>$d)
                {
                    $phone = $phones->where('user_id' , $d['user_id'])->first();
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
                    $d['created_at'] = $todayTime;
                    $d['detail'] = $json;
                    $d['new'] = $c;
                    $d['date'] = $today;
                    $d['phone_country'] = $phone['user_phone_country'];
                    $d['phone'] = $phone['user_phone'];
                    $data[$i] = $d;
                }
                DB::table('data_active_users')->insert($data);
            }
        });
    }
}
