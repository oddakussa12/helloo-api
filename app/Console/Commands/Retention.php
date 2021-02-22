<?php

namespace App\Console\Commands;


use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class Retention extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:retention {country} {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate retention';

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
        $date = $this->argument('date');
        $this->retention($country , $date);
    }

    public function retention($country , $date=null)
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
            $dauTable = 'dau_'.Carbon::yesterday($tz)->format('Ym');
            $yesterday = Carbon::yesterday($tz);
            $startTime = Carbon::yesterday($tz)->startOfDay();
            $endTime = Carbon::yesterday($tz)->endOfDay();
        }else{
            $dauTable = 'dau_'.Carbon::createFromFormat('Y-m-d' , $date , $tz)->format('Ym');
            $yesterday = Carbon::createFromFormat('Y-m-d' , $date , $tz);
            $startTime = Carbon::createFromFormat('Y-m-d' , $date , $tz)->startOfDay();
            $endTime = Carbon::createFromFormat('Y-m-d' , $date , $tz)->endOfDay();
        }
        dump($date);
        dump($yesterday);
        dump($startTime);
        dump($endTime);
        die;

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
        //t_data_retentions
        $start = '';
        DB::table('users_countries')
            ->where('activation' , 1)
            ->where('country' , $country_code)
            ->where('created_at' , '>=' , $startTime)
            ->where('created_at' , '<=' , $endTime)
            ->orderByDesc('user_id')->chunk(200 , function ($users) use ($start , $tz , &$num , &$tomorrowNum , &$twoNum , &$threeNum , &$sevenNum , &$thirtyNum){
                $num = $num+count($users);
                $userIds = $users->pluck('user_id')->all();
                $tomorrow = Carbon::createFromFormat('Y-m-d' , $start , $tz)->addDays(1);
                $s = $tomorrow->startOfDay()->timestamp;
                $e = $tomorrow->endOfDay()->timestamp;
                $pm = Carbon::createFromTimestamp($s)->format('Ym');
                $nm = Carbon::createFromTimestamp($e)->format('Ym');
                if($pm==$nm)
                {
                    $tomorrowTable = 'visit_logs_'.$pm;
                    $tomorrowT = DB::table($tomorrowTable)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $tomorrow->startOfDay()->timestamp)
                        ->where('visited_at' , '<=' , $tomorrow->endOfDay()->timestamp)->count(DB::raw('DISTINCT(user_id)'));
                }else{
                    $tomorrowPT = DB::able('visit_logs_'.$pm)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $s)->count(DB::raw('DISTINCT(user_id)'));
                    $tomorrowNT = DB::table('visit_logs_'.$nm)->whereIn('user_id' , $userIds)->where('visited_at' , '<=' , $e)->count(DB::raw('DISTINCT(user_id)'));
                    $tomorrowT = $tomorrowPT+$tomorrowNT;
                }
                $tomorrowNum = $tomorrowNum+$tomorrowT;



                $twoDays = Carbon::createFromFormat('Y-m-d' , $start , $tz)->addDays(2);
                $s = $twoDays->startOfDay()->timestamp;
                $e = $twoDays->endOfDay()->timestamp;
                $pm = Carbon::createFromTimestamp($s)->format('Ym');
                $nm = Carbon::createFromTimestamp($e)->format('Ym');
                if($pm==$nm)
                {
                    $twoDaysTable = 'visit_logs_'.$pm;
                    $twoDaysT = DB::table($twoDaysTable)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $twoDays->startOfDay()->timestamp)
                        ->where('visited_at' , '<=' , $twoDays->endOfDay()->timestamp)->count(DB::raw('DISTINCT(user_id)'));
                }else{
                    $twoDaysPT = DB::table('visit_logs_'.$pm)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $s)->count(DB::raw('DISTINCT(user_id)'));
                    $twoDaysNT = DB::table('visit_logs_'.$nm)->whereIn('user_id' , $userIds)->where('visited_at' , '<=' , $e)->count(DB::raw('DISTINCT(user_id)'));
                    $twoDaysT = $twoDaysPT+$twoDaysNT;
                }
                $twoNum = $twoNum+$twoDaysT;



                $threeDays= Carbon::createFromFormat('Y-m-d' , $start , $tz)->addDays(3);
                $s = $threeDays->startOfDay()->timestamp;
                $e = $threeDays->endOfDay()->timestamp;
                $pm = Carbon::createFromTimestamp($s)->format('Ym');
                $nm = Carbon::createFromTimestamp($e)->format('Ym');
                if($pm==$nm)
                {
                    $threeDaysTable = 'visit_logs_'.$pm;
                    $threeDaysT = DB::table($threeDaysTable)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $threeDays->startOfDay()->timestamp)
                        ->where('visited_at' , '<=' , $threeDays->endOfDay()->timestamp)->count(DB::raw('DISTINCT(user_id)'));
                }else{
                    $threeDaysPT = DB::table('visit_logs_'.$pm)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $s)->count(DB::raw('DISTINCT(user_id)'));
                    $threeDaysNT = DB::table('visit_logs_'.$nm)->whereIn('user_id' , $userIds)->where('visited_at' , '<=' , $e)->count(DB::raw('DISTINCT(user_id)'));
                    $threeDaysT = $threeDaysPT+$threeDaysNT;
                }
                $threeNum = $threeNum+$threeDaysT;



                $sevenDays= Carbon::createFromFormat('Y-m-d' , $start , $tz)->addDays(7);
                $s = $sevenDays->startOfDay()->timestamp;
                $e = $sevenDays->endOfDay()->timestamp;
                $pm = Carbon::createFromTimestamp($s)->format('Ym');
                $nm = Carbon::createFromTimestamp($e)->format('Ym');
                if($pm==$nm)
                {
                    $sevenDaysTable = 'visit_logs_'.$pm;
                    $sevenDaysT = DB::table($sevenDaysTable)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $sevenDays->startOfDay()->timestamp)
                        ->where('visited_at' , '<=' , $sevenDays->endOfDay()->timestamp)->count(DB::raw('DISTINCT(user_id)'));
                }else{
                    $sevenDaysPT = DB::table('visit_logs_'.$pm)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $s)->count(DB::raw('DISTINCT(user_id)'));
                    $sevenDaysNT = DB::table('visit_logs_'.$nm)->whereIn('user_id' , $userIds)->where('visited_at' , '<=' , $e)->count(DB::raw('DISTINCT(user_id)'));
                    $sevenDaysT = $sevenDaysPT+$sevenDaysNT;
                }
                $sevenNum = $sevenNum+$sevenDaysT;



                $thirtyDays= Carbon::createFromFormat('Y-m-d' , $start , $tz)->addDays(30);
                $s = $thirtyDays->startOfDay()->timestamp;
                $e = $thirtyDays->endOfDay()->timestamp;
                $pm = Carbon::createFromTimestamp($s)->format('Ym');
                $nm = Carbon::createFromTimestamp($e)->format('Ym');
                if($pm==$nm)
                {
                    $thirtyDaysTable = 'visit_logs_'.$pm;
                    $thirtyDaysT = DB::table($thirtyDaysTable)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $thirtyDays->startOfDay()->timestamp)
                        ->where('visited_at' , '<=' , $thirtyDays->endOfDay()->timestamp)->count(DB::raw('DISTINCT(user_id)'));
                }else{
                    $thirtyDaysPT = DB::table('visit_logs_'.$pm)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $s)->count(DB::raw('DISTINCT(user_id)'));
                    $thirtyDaysNT = DB::table('visit_logs_'.$nm)->whereIn('user_id' , $userIds)->where('visited_at' , '<=' , $e)->count(DB::raw('DISTINCT(user_id)'));
                    $thirtyDaysT = $thirtyDaysPT+$thirtyDaysNT;
                }
                $thirtyNum = $thirtyNum+$thirtyDaysT;


            });
    }

}
