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
        $key = 'helloo:account:service:account-au'.$date."_op_list"; //20191125
        $index = 1;
        $visitData = array();
        while(true) {
            $data = Redis::Lpop($key);
            if($data!==null)
            {
                array_push($visitData , \json_decode($data , true));
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
