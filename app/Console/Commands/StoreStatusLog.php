<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class StoreStatusLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'store:status_log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store Status Log';

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
        $key = 'helloo:account:service:account-status-change'.$date.'_list'; //20201206
        $index = 1;
        $visitData = array();
        while(true) {
            $data = Redis::Lpop($key);
            if($data!==null&&$data!==false)
            {
                $visit = \json_decode($data , true);
                $visit['created_at'] = $created_at;
                array_push($visitData , $visit);
            }
            if($index%100==0||$data===null||$data===false)
            {
                !blank($visitData)&&DB::table('status_logs_'.$suffix)->insert($visitData);
                $index = 0;
                $visitData = array();
                if($data===null||$data===false)
                {
                    break;
                }
            }
            $index++;
        }
    }
}
