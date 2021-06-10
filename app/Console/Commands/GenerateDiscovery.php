<?php

namespace App\Console\Commands;

use Carbon\Carbon;
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
        $this->popularShops();
        $this->ratedShops();
        $this->popularProducts();
        $this->ratedProducts();
    }

    private function popularShops()
    {
        $lastWeek = Carbon::now()->subWeek(1)->startOfWeek()->toDateTimeString();
        $limit = 50;
        $flag = true;
        $page = 0;
        $key = "helloo:discovery:popular:shops";
        Redis::del($key);
        do{
            $offset = $page*$limit;
            $sql = 'select `owner`,count(`id`) as `num` from `t_shops_views_logs` where `created_at` >= \''.$lastWeek.'\' group by `owner` order by `num` desc limit '.$limit.' offset '.$offset.';';
            $views = DB::select($sql);
            if(blank($views))
            {
                $flag = false;
            }else{
                $data = array();
                foreach ($views as $view)
                {
                    $data[$view->owner] = $view->num;
                }
                Redis::zadd($key , $data);
            }
            $page ++;
        }while($flag);
    }

    private function ratedShops()
    {
        $key = "helloo:discovery:rated:shops";
        Redis::del($key);
        DB::table('shop_evaluation_points')->orderByDesc('user_id')->chunk(100 , function($shops) use ($key){
            $data = array();
            foreach ($shops as $shop)
            {
                $point = $shop->point_1+$shop->point_2*2+$shop->point_3*3+$shop->point_4*4+$shop->point_5*5;
                $num = $shop->point_1+$shop->point_2+$shop->point_3+$shop->point_4+$shop->point_5;
                if($num>0)
                {
                    $data[$shop->user_id] = round($point/$num , 1);
                }
            }
            !empty($data)&&Redis::zadd($key , $data);
        });
    }


    private function popularProducts()
    {
        $lastWeek = Carbon::now()->subWeek(1)->startOfWeek()->toDateTimeString();
        $limit = 50;
        $flag = true;
        $page = 0;
        $key = "helloo:discovery:popular:products";
        Redis::del($key);
        do{
            $offset = $page*$limit;
            $sql = 'select `goods_id`,count(`id`) as `num` from `t_goods_views_logs` where `created_at` >= \''.$lastWeek.'\' group by `goods_id` order by `num` desc limit '.$limit.' offset '.$offset.';';
            $views = DB::select($sql);
            if(blank($views))
            {
                $flag = false;
            }else{
                $data = array();
                foreach ($views as $view)
                {
                    $data[$view->goods_id] = $view->num;
                }
                Redis::zadd($key , $data);
            }
            $page ++;
        }while($flag);
    }

    private function ratedProducts()
    {
        $limit = 50;
        $flag = true;
        $page = 0;
        $key = "helloo:discovery:rated:products";
        Redis::del($key);
        do{
            $offset = $page*$limit;
            $sql = 'select round(`point`/`comment` , 1) as `a_point`,`id` , `created_at` from `t_goods` where `comment`>0 order by `a_point` desc,`created_at` desc limit '.$limit.' offset '.$offset.';';
            $points = DB::select($sql);
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
            $page ++;
        }while($flag);
    }

}
