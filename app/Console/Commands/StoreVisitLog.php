<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class StoreVisitLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'store:visit_log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store Visit Log';

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
     * @return mixed
     */
    public function handle()
    {
        $yesterday = Carbon::yesterday('Asia/Shanghai');
        $date = $yesterday->format('Ymd');
        $created_at =  $yesterday->toDateString();
        $suffix = $yesterday->format('Ym');
        $key = 'helloo:account:service:account-au'.$date."_op_list"; //20191125
//        $key = 'helloo:account:service:account-au'.'20201118'."_op_list"; //20191125
        $index = 1;
        $visitData = array();
        $visitList = array();
        while(true) {
            $data = Redis::Lpop($key);
            if($data!==null&&$data!==false)
            {
                $visit = \json_decode($data , true);
                if(is_array($visit))
                {
                    $visit['created_at'] = $created_at;
                    if(isset($visit['device_id']))
                    {
                        array_push($visitData , $visit);
                    }else{
                        array_push($visitList , $visit);
                    }
                }
            }
            if($index%100==0||$data===null||$data===false)
            {
                !blank($visitData)&&DB::table('visit_logs_'.$suffix)->insert($visitData);
                !blank($visitList)&&DB::table('visit_logs_'.$suffix)->insert($visitList);
                $index = 0;
                $visitData = array();
                $visitList = array();
                if($data===null)
                {
                    break;
                }
            }
            $index++;
        }
    }
}
