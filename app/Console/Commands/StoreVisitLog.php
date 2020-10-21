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
        $date = $yesterday->toDateString();
        $key = 'au'.date('Ymd' , strtotime($yesterday)).'_op_list'; //20201021
        $index = 1;
        $visitData = array();
        while(true) {
            $data = Redis::Lpop($key);
            if($data!==null)
            {
                list($userId , $time) = explode('.' , $data);
                array_push($visitData , array(
                    'user_id'=>$userId,
                    'visited_at'=>$time,
                    'created_at'=>$date,
                ));
            }
            if($index%100==0||$data===null)
            {
                !blank($visitData)&&DB::table('visit_logs')->insert($visitData);
                $index = 0;
                $visitData = array();
                if($data===null)
                {
                    break;
                }
            }
            $index++;
        }

    }
}
