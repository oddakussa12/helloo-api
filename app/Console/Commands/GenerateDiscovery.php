<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class GenerateDiscovery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:discovery';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Discovery';

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
        $limit = 10;
        $offset = 0;
        $flag = true;
        $key = "helloo:discovery:popular:products";
        $points = DB::select('select round(`point`/`comment`) as `a_point`,`id` , `created_at` from `t_goods` where `comment`>0 order by `a_point` desc,`created_at` desc limit '.$limit.' offset '.$offset.';');
        do{
            if(blank($points))
            {
                $flag = false;
            }else{
                $data = array();
                foreach ($points as $point)
                {
                    $data[$point->id] = $point->a_point;
                }
                Redis::zadd($key , $data);
            }
        }while($flag);
    }

}
