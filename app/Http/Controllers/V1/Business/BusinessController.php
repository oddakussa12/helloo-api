<?php

namespace App\Http\Controllers\V1\Business;

use App\Models\User;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Models\Business\Goods;
use App\Jobs\BusinessSearchLog;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Redis;
use App\Resources\AnonymousCollection;
use App\Http\Controllers\V1\BaseController;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\GoodsRepository;
use Illuminate\Database\Concerns\BuildsQueries;

class BusinessController extends BaseController
{
    use BuildsQueries;

    public function search(Request $request)
    {
        $userId = intval(auth()->id());
        $keyword = escape_like(strval($request->input('keyword' , '')));
        if(!empty($keyword))
        {
            $users = User::where('user_shop' , 1)->where('user_verified' , 1)->where(function ($query) use ($keyword) {
                $query->where('user_nick_name', 'like', "{$keyword}%");
            })->limit(10)->get();
            $goods = Goods::where('name', 'like', "{$keyword}%")->limit(10)->get();
            $goodsIds = $goods->pluck('id')->toArray();
            if(!empty($goodsIds))
            {
                if($userId>0)
                {
                    $likes = collect(DB::table('likes_goods')->where('user_id' , $userId)->whereIn('goods_id' , $goodsIds)->get()->map(function ($value){
                        return (array)$value;
                    }))->pluck('goods_id')->unique()->toArray();
                }else{
                    $likes = array();
                }
                $goods->each(function($g) use ($likes){
                    $g->likeState = in_array($g->id , $likes);
                });
            }
        }else{
            $goods = $users = collect();
        }
        !empty($keyword)&&BusinessSearchLog::dispatch($userId , $keyword)->onQueue('helloo_{business_search_logs}');
        $users->each(function($user){
            $user->userPoint = app(UserRepository::class)->findPointByUserId($user->user_id);
        });
        return $this->response->array(array(
            'data'=>array(
                'user'=>UserCollection::collection($users),
                'goods'=>AnonymousCollection::collection($goods)
            )
        ));
    }

    public function searchV2(Request $request)
    {
        $appends = array();
        $userId = intval(auth()->id());
        $keyword = escape_like(strval($request->input('keyword' , '')));
        $tag = escape_like(strval($request->input('tag' , '')));
        $appends['keyword'] = $keyword;
        $appends['tag'] = $tag;
        if(!empty($keyword)&&!empty($tag))
        {
            $users = User::where('user_tag' , $tag)->where(function ($query) use ($keyword) {
                $query->where('user_nick_name', 'like', "{$keyword}%");
            })->orderByDesc('user_created_at')->paginate(10)->appends($appends);
        }else{
            $users = collect();
        }
        !empty($keyword)&&BusinessSearchLog::dispatch($userId , $keyword)->onQueue('helloo_{business_search_logs}');
        $users->each(function($user){
            $user->userPoint = app(UserRepository::class)->findPointByUserId($user->user_id);
        });
        return UserCollection::collection($users);
    }

    /**
     * @version 1.0
     * @note 店铺发现
     * @datetime 2021-07-12 17:48
     * @param Request $request
     * @return mixed
     */
    public function discovery(Request $request)
    {
        $deliveryUsers = app(UserRepository::class)->allWithBuilder()->where('user_activation' , 1)->where('user_shop' , 1)->where('user_verified' , 1)->where('user_delivery' , 1)->inRandomOrder()->limit(30)->get();
        $users = app(UserRepository::class)->allWithBuilder()->where('user_activation' , 1)->where('user_shop' , 1)->where('user_verified' , 1)->where('user_delivery' , 0)->inRandomOrder()->limit(20)->get();
        $users->each(function($user){
            $user->userPoint = app(UserRepository::class)->findPointByUserId($user->user_id);
        });
        $deliveryUsers->each(function($deliveryUser){
            $deliveryUser->userPoint = app(UserRepository::class)->findPointByUserId($deliveryUser->user_id);
        });
        $data = array('data'=>array(
            'live_shop'=>UserCollection::collection($users),
            'delivery_shop'=>UserCollection::collection($deliveryUsers),
        ));
        return $this->response->array($data);
    }

    /**
     *
     */
    public function discoveryIndex()
    {
        $deliveryUsers = app(UserRepository::class)
            ->allWithBuilder()->where('user_activation' , 1)
            ->where('user_shop' , 1)
            ->where('user_verified' , 1)
            ->where('user_delivery' , 1)
            ->orderByDesc('user_created_at')
            ->select(['user_id' , 'user_name' , 'user_nick_name' , 'user_avatar' , 'user_delivery' , 'user_shop' , 'user_bg' , 'user_address'])
            ->paginate(10);
        $deliveryUsers->each(function($deliveryUser){
            $deliveryUser->userPoint = app(UserRepository::class)->findPointByUserId($deliveryUser->user_id);
        });
        return UserCollection::collection($deliveryUsers);
    }

    /**
     * @version 2.0
     * @note 店铺发现
     * @datetime 2021-07-12 17:48
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function home(Request $request)
    {
        $appends = array();
        $type = $request->input('type' , 'product');
        $order = $request->input('order' , 'popular');
        $tag = $request->input('tag' , '');
        $sort = strval($request->input('sort' , 'desc'));
        $appends['type'] = $type;
        $appends['order'] = $order;
        $appends['tag'] = $tag;
        $appends['sort'] = $sort;
        $pageName = 'page';
        $page     = intval($request->input($pageName, 1));
        $perPage  = intval($request->input('per_page', 10));
        $perPage = $perPage<10?10:$perPage;
        $perPage = $perPage>50?50:$perPage;
        $offset   = ($page-1) * $perPage;
        if($type=='product')
        {
            if($order=='new')
            {
                $orderBy = $sort=='desc'?'orderByDesc':'orderBy';
                $goods = app(GoodsRepository::class)->allWithBuilder()->where('status' , 1)->$orderBy('created_at')->paginate($perPage , ['*'] , $pageName , $page)->appends($appends);
            }else{
                $key = 'helloo:discovery:'.$order.':products';
                if(Redis::exists($key))
                {
                    $total = Redis::zcard($key);
                    if($sort=='desc')
                    {
                        $goodsScoreIds = Redis::zrevrangebyscore($key , '+inf' , '-inf' , array('withscores'=>true , 'limit'=>array($offset , $perPage)));
                    }else{
                        $goodsScoreIds = Redis::zrangebyscore($key , '-inf' , '+inf' , array('withscores'=>true , 'limit'=>array($offset , $perPage)));
                    }
                    $goodsIds = array_keys($goodsScoreIds);
                }else {
                    $total = 0;
                    $goodsIds = $goodsScoreIds = array();
                }
                if(!empty($goodsIds))
                {
                    $goods = app(GoodsRepository::class)->allWithBuilder()->whereIn('id' , $goodsIds)->get();
                    $goods->each(function($g) use ($goodsScoreIds){
                        $g->setAttribute('score' , $goodsScoreIds[$g->id]);
                    });
                }else{
                    $goods = collect();
                }
                if($sort=='desc')
                {
                    $goods = $goods->sortByDesc('score')->values();
                }else{
                    $goods = $goods->sortBy('score')->values();
                }
                $goods->each(function($g){
                    $g->addHidden('score');
                });
                $goods = $this->paginator($goods , $total, $perPage, $page, [
                    'path'     => Paginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ])->appends($appends);
            }
            return AnonymousCollection::collection($goods);
        }elseif ($type=='shop')
        {
            if($order=='new')
            {
                $orderBy = $sort=='desc'?'orderByDesc':'orderBy';
                $shops = app(UserRepository::class)->allWithBuilder()->where('user_activation' , 1)->where('user_shop' , 1)->where('user_verified' , 1)->where('user_delivery' , 0);
                if(!empty($tag))
                {
                    $shops = $shops->where('tag' , $tag)->$orderBy('user_created_at')->paginate($perPage , ['*'] , $pageName , $page)->appends($appends);
                }else{
                    $shops = $shops->$orderBy('user_created_at')->paginate($perPage , ['*'] , $pageName , $page)->appends($appends);
                }
            }else{
                if(empty($tag))
                {
                    $key = 'helloo:discovery:'.$order.':shops';
                }else{
                    $key = 'helloo:discovery:'.$order.':'.$tag.':shops';
                }
                if(Redis::exists($key))
                {
                    $total = Redis::zcard($key);
                    if($sort=='desc')
                    {
                        $shopScoreIds = Redis::zrevrangebyscore($key , '+inf' , '-inf' , array('withscores'=>true , 'limit'=>array($offset , $perPage)));
                    }else{
                        $shopScoreIds = Redis::zrangebyscore($key , '-inf' , '+inf' , array('withscores'=>true , 'limit'=>array($offset , $perPage)));
                    }
                    $shopIds = array_keys($shopScoreIds);
                }else {
                    $total = 0;
                    $shopIds = $shopScoreIds = array();
                }
                if(!empty($shopIds))
                {
                    $shops = app(UserRepository::class)->allWithBuilder()->whereIn('user_id' , $shopIds)->get();
                    $shops->each(function($shop) use ($shopScoreIds){
                        $shop->setAttribute('score' , $shopScoreIds[$shop->user_id]);
                    });
                }else{
                    $shops = collect();
                }
                if($sort=='desc')
                {
                    $shops = $shops->sortByDesc('score')->values();
                }else{
                    $shops = $shops->sortBy('score')->values();
                }
                $shops->each(function($shop){
                    $shop->addHidden('score');
                });
                $shops = $this->paginator($shops, $total, $perPage, $page, [
                    'path'     => Paginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ])->appends($appends);
            }
            $shops->each(function($shop){
                $shop->userPoint = app(UserRepository::class)->findPointByUserId($shop->user_id);
            });
            return UserCollection::collection($shops);
        }else{
            $data = $this->paginator(collect(), 0, $perPage, $page, [
                'path'     => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]);
            return AnonymousCollection::collection($data);
        }
    }

    public function deliveryCost(Request $request)
    {
        $user = auth()->user();
        $jti = JWTAuth::getClaim('jti');
        $location = (array)$request->input('location' , array());
        $location = array_slice($location , 0 , 3);
        $distances = array();
        $location = array_filter($location , function ($v , $k){
            return isset($v['shop_id'])&&isset($v['start'])&&count($v['start'])==2;
        } , ARRAY_FILTER_USE_BOTH);
        $shopIds = array_column($location , 'shop_id');
        $addresses = DB::table('shops_addresses')->whereIn('shop_id' , $shopIds)->get();
        $location = array_map(function($v) use ($addresses){
            $address = $addresses->where('shop_id' , $v['shop_id'])->first();
            return array(
                'shop_id'=>$v['shop_id'],
                'start'=>$v['start'],
                'end'=>empty($address)?array(0 , 0):array(floatval($address->longitude) , floatval($address->latitude)),
            );
        } , $location);
        $url = config('common.mapbox_endpoint');
        $path = "/directions/v5/mapbox/driving/";
        $urls = array_map(function ($v) use ($url , $path){
            $startPoint = trim(array_reduce($v['start'] , function ($v1 , $v2){
                return $v1 . "," . $v2;
            }) , ',');
            $endPoint = trim(array_reduce($v['end'] , function ($v1 , $v2){
                return $v1 . "," . $v2;
            }) , ',');
            return $url.$path.$startPoint.';'.$endPoint;
        } , $location);
        $total = count($urls);
        $client = new Client(['timeout'=>5]);
        $data = array(
            'steps'=>'false',
            'alternatives'=>'true',
            'geometries'=>'geojson',
            'access_token'=>config('common.mapbox_access_token'),
        );
        $params = http_build_query($data);
        try{
            $requests = function ($total) use ($client , $urls , $params) {
                foreach ($urls as $url) {
                    $uri = $url.'?'.$params;
                    yield function() use ($client, $uri) {
                        return $client->getAsync($uri);
                    };
                }
            };
            $pool = new Pool($client, $requests($total), [
                'concurrency' => $total,
                'fulfilled'   => function ($response, $index) use ($location , $user , &$distances){
                    $routes = json_decode($response->getBody()->getContents() , true);
                    if(!isset($routes['routes'][0])||!isset($routes['waypoints']))
                    {
                        abort(500 , 'The result is abnormal!');
                    }
                    $route = $routes['routes'][0];
                    $waypoints = $routes['waypoints'];
                    $distance = $route['distance'];
                    switch ($distance)
                    {
                        case $distance<=3000:
                            $deliveryCost=45;
                            break;
                        case $distance>3000&&$distance<=6000:
                            $deliveryCost=65;
                            break;
                        case $distance>6000&&$distance<=9000:
                            $deliveryCost=85;
                            break;
                        default:
                            $deliveryCost=100;
                            break;
                    }
                    $data = array(
                        'start'=>[
                            'location'=>$location[$index]['start'],
                            'name'=>$waypoints[0]['name']
                        ],
                        'end'=>[
                            'location'=>$location[$index]['end'],
                            'name'=>$waypoints[1]['name']
                        ],
                        'shop_id'=>$location[$index]['shop_id'],
                        'distance'=>$route['distance'],
                        'delivery_cost'=>$deliveryCost,
                        'currency'=>$user->user_currency
                    );
                    array_push($distances , $data);
                },
                'rejected' => function ($reason, $index) use ($location , $user , &$distances){
                    $data = array(
                        'start'=>[
                            'location'=>$location[$index]['start'],
                            'name'=>''
                        ],
                        'end'=>[
                            'location'=>$location[$index]['end'],
                            'name'=>''
                        ],
                        'shop_id'=>$location[$index]['shop_id'],
                        'distance'=>-1,
                        'delivery_cost'=>100,
                        'currency'=>$user->user_currency
                    );
                    array_push($distances , $data);
                },
            ]);
            $promise = $pool->promise();
            $promise->wait();
        }catch (\Exception $e)
        {
            Log::info('delivery_cost_fail' , array(
                'data'=>$request->all(),
                'message'=>$e->getMessage(),
            ));
            foreach ($location as $index=>$v)
            {
                $data = array(
                    'start'=>[
                        'location'=>$v['start'],
                        'name'=>''
                    ],
                    'end'=>[
                        'location'=>$v['end'],
                        'name'=>''
                    ],
                    'shop_id'=>$v['shop_id'],
                    'distance'=>-1,
                    'delivery_cost'=>100,
                    'currency'=>$user->user_currency
                );
                array_push($distances , $data);
            }
        }
        $secret = array();
        foreach ($distances as $d)
        {
            $secret[$d['shop_id']] = array(
                'distance'=>$d['distance'],
                'delivery_cost'=>$d['delivery_cost'],
                'start'=>$d['start']['location'],
                'end'=>$d['end']['location'],
            );
        }
        Log::info('$secret' , $secret);
        $str = opensslEncrypt(\json_encode($secret) , $jti);
        Log::info('$str' , array($str , opensslDecryptV2($str , $jti)));
        return $this->response->array(array('data'=>$distances))->withHeader('delivery_coast' , $str);
    }

    public function specialGoods()
    {
        $count = DB::table('special_goods')->where('status' , 1)->count();
        $key = 'helloo:business:special_goods:image';
        $image = Redis::get($key);
        return $this->response->array(array(
            'data'=>array(
                'count'=>$count,
                'image'=>$image?:'https://test.image.helloo.mantouhealth.com/other/20210804/263383725620854784.jpg'
            )
        ));
    }

    public function shipDayCallback()
    {
        Log::info('all' , request()->all());
    }
}
