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
        $today = Carbon::now('Asia/Shanghai')->format('Y-m-d');//2021-02-23
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
        $thirtyDateStart = Carbon::createFromFormat('Y-m-d' , $today , $tz)->subDays(2)->subDays(30)->startOfDay()->timestamp;
        $thirtyDateEnd = Carbon::createFromFormat('Y-m-d' , $today , $tz)->subDays(2)->subDays(30)->endOfDay()->timestamp;


        $fourteenDateStart = Carbon::createFromFormat('Y-m-d' , $today , $tz)->subDays(2)->subDays(14)->startOfDay()->timestamp;
        $fourteenDateEnd = Carbon::createFromFormat('Y-m-d' , $today , $tz)->subDays(2)->subDays(14)->endOfDay()->timestamp;


        $sevenDateStart = Carbon::createFromFormat('Y-m-d' , $today , $tz)->subDays(2)->subDays(7)->startOfDay()->timestamp;
        $sevenDateEnd = Carbon::createFromFormat('Y-m-d' , $today , $tz)->subDays(2)->subDays(7)->endOfDay()->timestamp;


        $threeDateStart = Carbon::createFromFormat('Y-m-d' , $today , $tz)->subDays(2)->subDays(3)->startOfDay()->timestamp;
        $threeDateEnd = Carbon::createFromFormat('Y-m-d' , $today , $tz)->subDays(2)->subDays(3)->endOfDay()->timestamp;


        $twoDateStart = Carbon::createFromFormat('Y-m-d' , $today , $tz)->subDays(2)->subDays(2)->startOfDay()->timestamp;
        $twoDateEnd = Carbon::createFromFormat('Y-m-d' , $today , $tz)->subDays(2)->subDays(2)->endOfDay()->timestamp;


        $oneDateStart = Carbon::createFromFormat('Y-m-d' , $today , $tz)->subDays(2)->subDays(1)->startOfDay()->timestamp;
        $oneDateEnd = Carbon::createFromFormat('Y-m-d' , $today , $tz)->subDays(2)->subDays(1)->endOfDay()->timestamp;


        $nowStart = $endTime = Carbon::createFromFormat('Y-m-d' , $today , $tz)->subDays(2)->startOfDay()->timestamp;

        $nowEnd = $endTime = Carbon::createFromFormat('Y-m-d' , $today , $tz)->subDays(2)->endOfDay()->timestamp;


        $pm = Carbon::createFromTimestamp($nowStart , 'Asia/Shanghai')->format('Ym');
        $nm = Carbon::createFromTimestamp($nowEnd , 'Asia/Shanghai')->format('Ym');


        $thirtyDateSignUpCount = $fourteenDateSignUpCount = $sevenDateSignUpCount = $threeDateSignUpCount = $twoDateSignUpCount = $oneDateSignUpCount = 0;

        $thirtyDateKeepCount = $fourteenDateKeepCount = $sevenDateKeepCount = $threeDateKeepCount = $twoDateKeepCount = $oneDateKeepCount = 0;

        //30
        DB::table('users_countries')
            ->where('activation' , 1)
            ->where('country' , $country)
            ->where('created_at' , '>=' , Carbon::createFromTimestamp($thirtyDateStart , new \DateTimeZone('UTC'))->toDateTimeString())
            ->where('created_at' , '<=' , Carbon::createFromTimestamp($thirtyDateEnd , new \DateTimeZone('UTC'))->toDateTimeString())
            ->orderByDesc('user_id')->chunk(100 , function($users) use ($nowStart , $nowEnd , $pm , $nm , &$thirtyDateSignUpCount , &$thirtyDateKeepCount){
                $thirtyDateSignUpCount = $thirtyDateSignUpCount+count($users);
                $userIds = $users->pluck('user_id')->all();
                if($pm==$nm)
                {
                    $visitTable = 'visit_logs_'.$pm;
                    $keepT = DB::table($visitTable)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $nowStart)
                        ->where('visited_at' , '<=' , $nowEnd)->count(DB::raw('DISTINCT(user_id)'));
                }else{
                    $keepPT = DB::table('visit_logs_'.$pm)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $nowStart)->count(DB::raw('DISTINCT(user_id)'));
                    $keepNT = DB::table('visit_logs_'.$nm)->whereIn('user_id' , $userIds)->where('visited_at' , '<=' , $nowEnd)->count(DB::raw('DISTINCT(user_id)'));
                    $keepT = $keepPT+$keepNT;
                }
                $thirtyDateKeepCount = $thirtyDateKeepCount+$keepT;
            });
        $thirty = Carbon::createFromFormat('Y-m-d' , $today , $tz)->subDays(2)->subDays(30)->toDateString();
        $thirtyData = array(
            'new'=>$thirtyDateSignUpCount,
            '30'=>$thirtyDateKeepCount
        );
        $result = DB::table('data_retentions')->where('country' , $country)->where('date' , $thirty)->update($thirtyData);
        Log::info('thirty' , array(
            $thirtyData,$result,$thirty
        ));


        //14
        DB::table('users_countries')
            ->where('activation' , 1)
            ->where('country' , $country)
            ->where('created_at' , '>=' , Carbon::createFromTimestamp($fourteenDateStart , new \DateTimeZone('UTC'))->toDateTimeString())
            ->where('created_at' , '<=' , Carbon::createFromTimestamp($fourteenDateEnd , new \DateTimeZone('UTC'))->toDateTimeString())
            ->orderByDesc('user_id')->chunk(100 , function($users) use ($nowStart , $nowEnd , $pm , $nm , &$fourteenDateSignUpCount , &$fourteenDateKeepCount){
                $fourteenDateSignUpCount = $fourteenDateSignUpCount+count($users);
                $userIds = $users->pluck('user_id')->all();
                if($pm==$nm)
                {
                    $visitTable = 'visit_logs_'.$pm;
                    $keepT = DB::table($visitTable)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $nowStart)
                        ->where('visited_at' , '<=' , $nowEnd)->count(DB::raw('DISTINCT(user_id)'));
                }else{
                    $keepPT = DB::table('visit_logs_'.$pm)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $nowStart)->count(DB::raw('DISTINCT(user_id)'));
                    $keepNT = DB::table('visit_logs_'.$nm)->whereIn('user_id' , $userIds)->where('visited_at' , '<=' , $nowEnd)->count(DB::raw('DISTINCT(user_id)'));
                    $keepT = $keepPT+$keepNT;
                }
                $fourteenDateKeepCount = $fourteenDateKeepCount+$keepT;
            });
        $fourteen = Carbon::createFromFormat('Y-m-d' , $today , $tz)->subDays(2)->subDays(14)->toDateString();
        $fourteenData = array(
            'new'=>$fourteenDateSignUpCount,
            '14'=>$fourteenDateKeepCount
        );
        $result = DB::table('data_retentions')->where('country' , $country)->where('date' , $fourteen)->update($fourteenData);
        Log::info('fourteen' , array(
            $fourteenData,$result,$fourteen
        ));

        //7
        DB::table('users_countries')
            ->where('activation' , 1)
            ->where('country' , $country)
            ->where('created_at' , '>=' , Carbon::createFromTimestamp($sevenDateStart , new \DateTimeZone('UTC'))->toDateTimeString())
            ->where('created_at' , '<=' , Carbon::createFromTimestamp($sevenDateEnd , new \DateTimeZone('UTC'))->toDateTimeString())
            ->orderByDesc('user_id')->chunk(100 , function($users) use ($nowStart , $nowEnd , $pm , $nm , &$sevenDateSignUpCount , &$sevenDateKeepCount){
                $sevenDateSignUpCount = $sevenDateSignUpCount+count($users);
                $userIds = $users->pluck('user_id')->all();
                if($pm==$nm)
                {
                    $visitTable = 'visit_logs_'.$pm;
                    $keepT = DB::table($visitTable)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $nowStart)
                        ->where('visited_at' , '<=' , $nowEnd)->count(DB::raw('DISTINCT(user_id)'));
                }else{
                    $keepPT = DB::table('visit_logs_'.$pm)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $nowStart)->count(DB::raw('DISTINCT(user_id)'));
                    $keepNT = DB::table('visit_logs_'.$nm)->whereIn('user_id' , $userIds)->where('visited_at' , '<=' , $nowEnd)->count(DB::raw('DISTINCT(user_id)'));
                    $keepT = $keepPT+$keepNT;
                }
                $sevenDateKeepCount = $sevenDateKeepCount+$keepT;
            });
        $seven = Carbon::createFromFormat('Y-m-d' , $today , $tz)->subDays(2)->subDays(7)->toDateString();
        $sevenData = array(
            'new'=>$sevenDateSignUpCount,
            '7'=>$sevenDateKeepCount
        );
        $result = DB::table('data_retentions')->where('country' , $country)->where('date' , $seven)->update($sevenData);
        Log::info('seven' , array(
            $fourteenData,$result,$seven
        ));


        //3
        DB::table('users_countries')
            ->where('activation' , 1)
            ->where('country' , $country)
            ->where('created_at' , '>=' , Carbon::createFromTimestamp($threeDateStart , new \DateTimeZone('UTC'))->toDateTimeString())
            ->where('created_at' , '<=' , Carbon::createFromTimestamp($threeDateEnd , new \DateTimeZone('UTC'))->toDateTimeString())
            ->orderByDesc('user_id')->chunk(100 , function($users) use ($nowStart , $nowEnd , $pm , $nm , &$threeDateSignUpCount , &$threeDateKeepCount){
                $threeDateSignUpCount = $threeDateSignUpCount+count($users);
                $userIds = $users->pluck('user_id')->all();
                if($pm==$nm)
                {
                    $visitTable = 'visit_logs_'.$pm;
                    $keepT = DB::table($visitTable)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $nowStart)
                        ->where('visited_at' , '<=' , $nowEnd)->count(DB::raw('DISTINCT(user_id)'));
                }else{
                    $keepPT = DB::table('visit_logs_'.$pm)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $nowStart)->count(DB::raw('DISTINCT(user_id)'));
                    $keepNT = DB::table('visit_logs_'.$nm)->whereIn('user_id' , $userIds)->where('visited_at' , '<=' , $nowEnd)->count(DB::raw('DISTINCT(user_id)'));
                    $keepT = $keepPT+$keepNT;
                }
                $threeDateKeepCount = $threeDateKeepCount+$keepT;
            });
        $three = Carbon::createFromFormat('Y-m-d' , $today , $tz)->subDays(2)->subDays(3)->toDateString();
        $threeData = array(
            'new'=>$threeDateSignUpCount,
            '3'=>$threeDateKeepCount
        );
        $result = DB::table('data_retentions')->where('country' , $country)->where('date' , $three)->update($sevenData);
        Log::info('three' , array(
            $threeData,$result,$three
        ));


        //2
        DB::table('users_countries')
            ->where('activation' , 1)
            ->where('country' , $country)
            ->where('created_at' , '>=' , Carbon::createFromTimestamp($twoDateStart , new \DateTimeZone('UTC'))->toDateTimeString())
            ->where('created_at' , '<=' , Carbon::createFromTimestamp($twoDateEnd , new \DateTimeZone('UTC'))->toDateTimeString())
            ->orderByDesc('user_id')->chunk(100 , function($users) use ($nowStart , $nowEnd , $pm , $nm , &$twoDateSignUpCount , &$twoDateKeepCount){
                $twoDateSignUpCount = $twoDateSignUpCount+count($users);
                $userIds = $users->pluck('user_id')->all();
                if($pm==$nm)
                {
                    $visitTable = 'visit_logs_'.$pm;
                    $keepT = DB::table($visitTable)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $nowStart)
                        ->where('visited_at' , '<=' , $nowEnd)->count(DB::raw('DISTINCT(user_id)'));
                }else{
                    $keepPT = DB::table('visit_logs_'.$pm)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $nowStart)->count(DB::raw('DISTINCT(user_id)'));
                    $keepNT = DB::table('visit_logs_'.$nm)->whereIn('user_id' , $userIds)->where('visited_at' , '<=' , $nowEnd)->count(DB::raw('DISTINCT(user_id)'));
                    $keepT = $keepPT+$keepNT;
                }
                $twoDateKeepCount = $twoDateKeepCount+$keepT;
            });
        $two = Carbon::createFromFormat('Y-m-d' , $today , $tz)->subDays(2)->subDays(2)->toDateString();
        $twoData = array(
            'new'=>$twoDateSignUpCount,
            '3'=>$twoDateKeepCount
        );
        $result = DB::table('data_retentions')->where('country' , $country)->where('date' , $two)->update($twoData);
        Log::info('two' , array(
            $twoData,$result,$two
        ));


        //1
        DB::table('users_countries')
            ->where('activation' , 1)
            ->where('country' , $country)
            ->where('created_at' , '>=' , Carbon::createFromTimestamp($oneDateStart , new \DateTimeZone('UTC'))->toDateTimeString())
            ->where('created_at' , '<=' , Carbon::createFromTimestamp($oneDateEnd , new \DateTimeZone('UTC'))->toDateTimeString())
            ->orderByDesc('user_id')->chunk(100 , function($users) use ($nowStart , $nowEnd , $pm , $nm , &$oneDateSignUpCount , &$oneDateKeepCount){
                $oneDateSignUpCount = $oneDateSignUpCount+count($users);
                $userIds = $users->pluck('user_id')->all();
                if($pm==$nm)
                {
                    $visitTable = 'visit_logs_'.$pm;
                    $keepT = DB::table($visitTable)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $nowStart)
                        ->where('visited_at' , '<=' , $nowEnd)->count(DB::raw('DISTINCT(user_id)'));
                }else{
                    $keepPT = DB::table('visit_logs_'.$pm)->whereIn('user_id' , $userIds)->where('visited_at' , '>=' , $nowStart)->count(DB::raw('DISTINCT(user_id)'));
                    $keepNT = DB::table('visit_logs_'.$nm)->whereIn('user_id' , $userIds)->where('visited_at' , '<=' , $nowEnd)->count(DB::raw('DISTINCT(user_id)'));
                    $keepT = $keepPT+$keepNT;
                }
                $oneDateKeepCount = $oneDateKeepCount+$keepT;
            });
        $one = Carbon::createFromFormat('Y-m-d' , $today , $tz)->subDays(2)->subDays(1)->toDateString();
        $result = DB::table('data_retentions')->where('country' , $country)->where('date' , $one)->first();
        if(blank($result))
        {
            $data = array(
                'date'=>$date,
                'country'=>$country,
                'new'=>$oneDateSignUpCount,
                '1'=>$oneDateKeepCount,
                'created_at'=>Carbon::now()->toDateTimeString()
            );
            $result = DB::table('data_retentions')->where('country' , $country)->insert($data);
        }else{
            $data = array(
                'new'=>$oneDateSignUpCount,
                '1'=>$oneDateKeepCount
            );
            $result = DB::table('data_retentions')->where('country' , $country)->where('date' , $one)->update($data);
        }
        Log::info('one' , array(
            $data,$result,$one
        ));


    }

}
