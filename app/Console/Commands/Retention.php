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
        $now = $startTime = Carbon::yesterday($tz)->subDays(30);
        $one = Carbon::yesterday($tz)->subDays(29);
        $two = Carbon::yesterday($tz)->subDays(28);
        $three = Carbon::yesterday($tz)->subDays(27);
        $seven = Carbon::yesterday($tz)->subDays(21);
        $fourteen = Carbon::yesterday($tz)->subDays(16);
        $thirty = $endTime = Carbon::yesterday($tz);
        $start = '2021-01-01';
        $end = '2021-02-21';
        $country_code = $country;
        while($start<=$end)
        {

            $count = 0;
            $startTime = Carbon::createFromTimestamp(Carbon::createFromFormat('Y-m-d' , $start , $tz)->startOfDay()->timestamp , new \DateTimeZone('UTC'))->toDateTimeString();
            $endTime = Carbon::createFromTimestamp(Carbon::createFromFormat('Y-m-d' , $start , $tz)->endOfDay()->timestamp , new \DateTimeZone('UTC'))->toDateTimeString();
            DB::table('users_countries')
                ->where('activation' , 1)
                ->where('country' , $country_code)
                ->where('created_at' , '>=' , $startTime)
                ->where('created_at' , '<=' , $endTime)
                ->orderByDesc('user_id')->chunk(200 , function ($users) use(&$count) {
                    $count = $count+count($users);
                    

                });


            $start = Carbon::createFromFormat('Y-m-d' , $start)->addDays(1)->toDateString();
        }



        echo Carbon::yesterday($tz)->toDateTimeString();
        echo PHP_EOL;
        $thirtyDaysAgo = Carbon::yesterday($tz)->subDays(30);
        echo $thirtyDaysAgo->toDateTimeString();
        die;
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

        dump($startTime);
        dump($endTime);
        $startTime = Carbon::createFromTimestamp($s , new \DateTime('UTC'))->toDateTimeString();
        $endTime = Carbon::createFromTimestamp($e , new \DateTime('UTC'))->toDateTimeString();

        dump($startTime);
        dump($endTime);
        die;
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
        DB::table('users_countries')
            ->where('activation' , 1)
            ->where('country' , $country_code)
            ->where('created_at' , '>=' , $startTime)
            ->where('created_at' , '<=' , $endTime)
            ->orderByDesc('user_id')->chunk(200 , function ($users) {



            });
    }

}
