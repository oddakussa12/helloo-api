<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Repositories\Contracts\UserRepository;

class GenerateDiscovery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:discovery  {type?}';

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
        $type = $this->argument('type');
        if($type=='popular')
        {
            $this->popularShops();
            $this->popularProducts();
        }else if($type=='rated'){
            $this->ratedShops();
            $this->ratedProducts();
        }else{
            $this->priceProducts();
        }
    }

    private function popularShops()
    {
        Redis::del("helloo:business:service:shop:tags");
        $lastWeek = Carbon::now()->subWeek(10)->startOfWeek()->toDateTimeString();
        $limit = 50;
        $flag = true;
        $page = 0;
        $key = "helloo:discovery:popular:shops";
        Redis::del($key);
        $tags = DB::table('shops_tags')->where('status' , 1)->select('tag')->distinct()->get()->map(function ($value) {return (array)$value;})->pluck('tag')->toArray();
        $keys = array();
        foreach ($tags as $tag)
        {
            array_push($keys , 'helloo:discovery:popular:'.$tag.':shops');
        }
        !empty($keys)&&Redis::del($keys);
        do{
            $offset = $page*$limit;
            $sql = 'select `owner`,count(`id`) as `num` from `t_shops_views_logs` where `created_at` >= \''.$lastWeek.'\' group by `owner` order by `num` desc limit '.$limit.' offset '.$offset.';';
            $views = DB::select($sql);
            if(blank($views))
            {
                $flag = false;
            }else{
                $data = array();
                $views = collect($views)->map(function ($value) {return (array)$value;})->toArray();
                $users = app(UserRepository::class)->findByUserIds(collect($views)->pluck('owner')->toArray());
                $userDelivery = $users->pluck('user_delivery' , 'user_id')->toArray();
                $userTag = $users->pluck('user_tag' , 'user_id')->toArray();
                foreach ($views as $view)
                {
                    if(isset($userDelivery[$view['owner']])&&$userDelivery[$view['owner']]==0)
                    {
                        $data[$view['owner']] = $view['num'];
                        if(!empty($userTag[$view['owner']]))
                        {
                            $tag = $userTag[$view['owner']];
                            $tagKey = 'helloo:discovery:popular:'.$tag.':shops';
                            !empty($tag)&&Redis::zadd($tagKey , array(
                                $view['owner'] => $view['num']
                            ));
                        }
                    }
                }
                !empty($data)&&Redis::zadd($key , $data);
            }
            $page ++;
        }while($flag);
    }

    private function ratedShops()
    {
        Redis::del("helloo:business:service:shop:tags");
        $key = "helloo:discovery:rated:shops";
        Redis::del($key);
        $tags = DB::table('shops_tags')->where('status' , 1)->select('tag')->distinct()->get()->map(function ($value) {return (array)$value;})->pluck('tag')->toArray();
        $keys = array();
        foreach ($tags as $tag)
        {
            array_push($keys , 'helloo:discovery:rated:'.$tag.':shops');
        }
        !empty($keys)&&Redis::del($keys);
        DB::table('shop_evaluation_points')->orderByDesc('user_id')->chunk(100 , function($shops) use ($key){
            $data = array();
            $users = app(UserRepository::class)->findByUserIds($shops->pluck('user_id')->toArray());
            $userDelivery = $users->pluck('user_delivery' , 'user_id')->toArray();
            $userTag = $users->pluck('user_tag' , 'user_id')->toArray();
            foreach ($shops as $shop)
            {
                $point = $shop->point_1+$shop->point_2*2+$shop->point_3*3+$shop->point_4*4+$shop->point_5*5;
                $num = $shop->point_1+$shop->point_2+$shop->point_3+$shop->point_4+$shop->point_5;
                if($num>0&&isset($userDelivery[$shop->user_id])&&$userDelivery[$shop->user_id]==0)
                {
                    $data[$shop->user_id] = round($point/$num , 1);
                    if(!empty($userTag[$shop->user_id]))
                    {
                        $tag = $userTag[$shop->user_id];
                        $tagKey = 'helloo:discovery:rated:'.$tag.':shops';
                        !empty($tag)&&Redis::zadd($tagKey , array(
                            $shop->user_id => round($point/$num , 1)
                        ));
                    }

                }
            }
            !empty($data)&&Redis::zadd($key , $data);
        });
    }


    private function popularProducts()
    {
        $lastWeek = Carbon::now()->subWeek(10)->startOfWeek()->toDateTimeString();
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
                $goods = DB::table('goods')->where('status' , 1)->whereIn('id' , collect($views)->pluck('goods_id')->toArray())->get();
                $goodsIds = $goods->pluck('id')->toArray();
                foreach ($views as $view)
                {
                    if(in_array($view->goods_id , $goodsIds))
                    {
                        $data[$view->goods_id] = $view->num;
                    }
                }
                !empty($data)&&Redis::zadd($key , $data);
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
            $sql = 'select round(`point`/`comment` , 1) as `a_point`,`id` , `created_at` from `t_goods` where `status`=1 and `comment`>0 order by `a_point` desc,`created_at` desc limit '.$limit.' offset '.$offset.';';
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

    private function priceProducts()
    {
        $limit = 50;
        $flag = true;
        $page = 0;
        $key = "helloo:discovery:price:products";
        Redis::del($key);
        do{
            $offset = $page*$limit;
            $goods = DB::table('goods')->where('status' , 1)->orderByDesc('created_at')->offset($offset)->limit($limit)->get();
            if(blank($goods))
            {
                $flag = false;
            }else{
                $data = array();
                foreach ($goods as $g)
                {
                    $price = $g->currency=='BIRR'?0.023*$g->price:$g->price;
                    $data[$g->id] = $price;
                }
                Redis::zadd($key , $data);
            }
            $page ++;
        }while($flag);
    }

}
