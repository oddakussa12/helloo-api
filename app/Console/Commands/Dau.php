<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Dau extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:dau {country} {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate dau';

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
        $country = $this->argument('country');
        Log::info('$country' , array($country));
        $date = $this->argument('date');
        $this->dau($country , $date);
    }

    public function dau($country , $date=null)
    {
        if($country=='tl')
        {
            $tz = "Asia/Dili";
            $hour = "9";
        }elseif ($country=='gd')
        {
            $hour = "-4";
            $tz = "America/Grenada";
        }elseif ($country=='mu')
        {
            $hour = "4";
            $tz = "Indian/Mauritius";
        }else{
            return;
        }
        if($date===null)
        {
            $dauTable = 'dau_'.Carbon::now($tz)->subDays(2)->format('Ym');
            $yesterday = Carbon::now($tz)->subDays(2);
            $startTime = Carbon::now($tz)->subDays(2)->startOfDay();
            $endTime = Carbon::now($tz)->subDays(2)->endOfDay();
        }else{
            $dauTable = 'dau_'.Carbon::createFromFormat('Y-m-d' , $date , $tz)->format('Ym');
            $yesterday = Carbon::createFromFormat('Y-m-d' , $date , $tz);
            $startTime = Carbon::createFromFormat('Y-m-d' , $date , $tz)->startOfDay();
            $endTime = Carbon::createFromFormat('Y-m-d' , $date , $tz)->endOfDay();
        }
        $s = $startTime->timestamp;
        $e = $endTime->timestamp;
        $date = $yesterday->toDateString();
        $pm = Carbon::createFromTimestamp($s , 'Asia/Shanghai')->format('Ym');
        $nm = Carbon::createFromTimestamp($e , 'Asia/Shanghai')->format('Ym');
        $count = $three = $two = $one = $zero = 0;
        $country_code = $country;
        $dua = DB::table($dauTable)->where('date' , $date)->where('country' , $country_code)->first();
        if(!blank($dua))
        {
            return;
        }
        Log::info('$country_'.$country , array(
            '$dauTable'=>$dauTable,
            '$yesterday'=>$yesterday,
            '$startTime'=>$startTime,
            '$endTime'=>$endTime,
            '$pm'=>$pm,
            '$nm'=>$nm,
            '$country_code'=>$country_code,
            '$date'=>$date,
            '$s'=>$s,
            '$e'=>$e,
        ));
        if($pm==$nm)
        {
            $daysTable = 'visit_logs_'.$pm;
            $daysChatTable = 't_ry_chats_'.$pm;
            DB::table($daysTable)->join('users_countries' , function($join) use ($daysTable , $country_code){
                $join->on($daysTable.'.user_id', '=', 'users_countries.user_id')
                    ->where('users_countries.country', $country_code)
                    ->where('users_countries.activation', 1);
            })->where($daysTable.'.visited_at' , '>=' , $s)
                ->where($daysTable.'.visited_at' , '<=' , $e)->select($daysTable.'.user_id')->distinct()->orderByDesc($daysTable.'.user_id')->chunk(100 , function($userIds)use($daysChatTable,$dauTable , $country_code,$date , &$count , &$three , &$two , &$one , $hour){
                    $userIds = $userIds->pluck('user_id')->values()->toArray();
                    $data = array();
                    foreach ($userIds as $userId)
                    {
                        array_push($data , array(
                            'user_id'=>$userId,
                            'date'=>$date,
                            'country'=>$country_code
                        ));
                    }
                    !blank($data)&&DB::table($dauTable)->insert($data);


                    $count = $count+count($userIds);
                    $userIds = trim(implode(',' , $userIds) , ',');
                    $sql = "select chat_from_id,count(*) as c from $daysChatTable where chat_from_id in ($userIds) and chat_msg_type='Helloo:VideoMsg' and date(date_add(from_unixtime(floor(chat_time/1000)),INTERVAL {$hour} HOUR))="."'$date' group by chat_from_id order by c desc";
                    $c = DB::select($sql);
                    $three3 = collect($c)->filter(function ($value, $key) {
                        return $value->c >= 3;
                    })->count();
                    $three = $three+$three3;
                    $two2 = collect($c)->filter(function ($value, $key) {
                        return $value->c == 2;
                    })->count();
                    $two = $two+$two2;
                    $one1 = collect($c)->filter(function ($value, $key) {
                        return $value->c == 1;
                    })->count();
                    $one = $one+$one1;
                });
            $zero = $count-$three-$two-$one;
            dump($count.'='.$zero.'='.$one.'='.$two.'='.$three);
            DB::table('dau_counts')->insert(array(
                'date'=>$date,
                'country'=>$country_code,
                'dau'=>$count,
                '0'=>$zero,
                '1'=>$one,
                '2'=>$two,
                'gt3'=>$three,
                'created_at'=>Carbon::now()->toDateTimeString(),
            ));
        }else{
            $daysTable = 'visit_logs_'.$pm;
            DB::table($daysTable)->join('users_countries' , function($join) use ($daysTable , $country_code){
                $join->on($daysTable.'.user_id', '=', 'users_countries.user_id')
                    ->where('users_countries.country', $country_code)
                    ->where('users_countries.activation', 1);
            })->where($daysTable.'.visited_at' , '>=' , $s)->select($daysTable.'.user_id')->distinct()->orderByDesc($daysTable.'.user_id')->chunk(100 , function($userIds)use($dauTable,$country_code,$date , &$count , &$three , &$two , &$one){
                    $userIds = $userIds->pluck('user_id')->values()->toArray();
                    $data = array();
                    foreach ($userIds as $userId)
                    {
                        array_push($data , array(
                            'user_id'=>$userId,
                            'date'=>$date,
                            'country'=>$country_code
                        ));
                    }
                    !blank($data)&&DB::table($dauTable)->insert($data);
                });
            $daysTable = 'visit_logs_'.$nm;

            DB::table($daysTable)->join('users_countries' , function($join) use ($daysTable , $country_code){
                $join->on($daysTable.'.user_id', '=', 'users_countries.user_id')
                    ->where('users_countries.country', $country_code)
                    ->where('users_countries.activation', 1);
            })->where($daysTable.'.visited_at' , '<=' , $e)->select($daysTable.'.user_id')->distinct()->orderByDesc($daysTable.'.user_id')->chunk(100 , function($userIds)use($dauTable,$country_code , $date , &$count , &$three , &$two , &$one){
                $userIds = $userIds->pluck('user_id')->values()->toArray();
                $exists = DB::table($dauTable)->where('country' , $country_code)->where('date' , $date)->whereIn('user_id' , $userIds)->pluck('user_id')->toArray();
                $userIds = array_diff($userIds , $exists);
                $data = array();
                foreach ($userIds as $userId)
                {
                    array_push($data , array(
                        'user_id'=>$userId,
                        'date'=>$date,
                        'country'=>$country_code
                    ));
                }
                !blank($data)&&DB::table($dauTable)->insert($data);
            });

            $count = $three = $two = $one = $zero = 0;
            $daysChatPTable = 't_ry_chats_'.$pm;
            $daysChatNTable = 't_ry_chats_'.$nm;
            DB::table($dauTable)->where('country' , $country_code)->where('date' , $date)->orderByDesc('id')->chunk(100 , function($userIds) use ($daysChatPTable , $daysChatNTable , $date , &$count , &$three , &$two , &$one , $hour){
                $data = $userIds->pluck('user_id')->values()->toArray();
                $count = $count+count($data);
                $userIds = trim(implode(',' , $data) , ',');


                $sql = "select chat_from_id,count(*) as c from $daysChatPTable where chat_from_id in ($userIds) and chat_msg_type='Helloo:VideoMsg' and date(date_add(from_unixtime(floor(chat_time/1000)),INTERVAL {$hour} HOUR))="."'$date' group by chat_from_id order by c desc";
                $c = DB::select($sql);
                $three3 = collect($c)->filter(function ($value, $key) {
                    return $value->c >= 3;
                })->count();
                $three = $three+$three3;
                $two2 = collect($c)->filter(function ($value, $key) {
                    return $value->c == 2;
                })->count();
                $two = $two+$two2;
                $one1 = collect($c)->filter(function ($value, $key) {
                    return $value->c == 1;
                })->count();
                $one = $one+$one1;


                $sql = "select chat_from_id,count(*) as c from $daysChatNTable where chat_from_id in ($userIds) and chat_msg_type='Helloo:VideoMsg' and date(date_add(from_unixtime(floor(chat_time/1000)),INTERVAL {$hour} HOUR))="."'$date' group by chat_from_id order by c desc";
                $c = DB::select($sql);
                $three3 = collect($c)->filter(function ($value, $key) {
                    return $value->c >= 3;
                })->count();
                $three = $three+$three3;
                $two2 = collect($c)->filter(function ($value, $key) {
                    return $value->c == 2;
                })->count();
                $two = $two+$two2;
                $one1 = collect($c)->filter(function ($value, $key) {
                    return $value->c == 1;
                })->count();

                $one = $one+$one1;
            });
            $zero = $count-$three-$two-$one;
            dump($count.'='.$zero.'='.$one.'='.$two.'='.$three);
            DB::table('dau_counts')->insert(array(
                'date'=>$date,
                'country'=>$country_code,
                'dau'=>$count,
                '0'=>$zero,
                '1'=>$one,
                '2'=>$two,
                'gt3'=>$three,
                'created_at'=>Carbon::now()->toDateTimeString(),
            ));
        }

    }

    public function __call($method, $parameters)
    {
        echo $method;
    }


}
