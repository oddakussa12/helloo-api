<?php

namespace App\Http\Controllers\V1\Business;

use App\Models\User;
use GuzzleHttp\Pool;
use Ramsey\Uuid\Uuid;
use GuzzleHttp\Client;
use App\Custom\RedisList;
use App\Jobs\ShipdayOrder;
use Illuminate\Http\Request;
use App\Models\Business\Goods;
use App\Models\Business\Order;
use App\Jobs\BusinessSearchLog;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use App\Models\Business\PromoCode;
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
        $userId = (int)auth()->id();
        $keyword = escape_like((string)$request->input('keyword', ''));
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
                    $g->likeState = in_array($g->id, $likes, true);
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
        $userId = (int)auth()->id();
        $keyword = escape_like((string)$request->input('keyword', ''));
        $tag = escape_like((string)$request->input('tag', ''));
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

    function computeDistance($lat1, $lng1, $lat2, $lng2, $radius = 6378137)
    {
        static $x = M_PI / 180;
        $lat1 *= $x; $lng1 *= $x;
        $lat2 *= $x; $lng2 *= $x;
        $distance = 2 * asin(sqrt(pow(sin(($lat1 - $lat2) / 2), 2) + cos($lat1) * cos($lat2) * pow(sin(($lng1 - $lng2) / 2), 2)));

        return $distance * $radius;
    }

    public function deliveryTime(Array $locationUser, Array $locationShop) //todo: transfer to proper class
    {
        $distance = $this->computeDistance($locationUser[0], $locationUser[1], $locationShop[0], $locationShop[1]);
        
        $speed = 3.33;
        if($distance < 10 * 1000) $speed = 3;
        if($distance < 5 * 1000) $speed = 2.6; //todo: use mapbox as in deliveryCost() for more predictable distance
        $t1 = (($distance / 1000) / $speed) * 60 * 60;
        if(is_nan($t1) || is_infinite($t1)) $t1 = 0;
    
        $t2 = 17 * 60 * 60; //17 mins for peackup
        $t3 = 3 * 60 * 60; //3 mins for call center work

        return $t1;// + $t2 + $t3;

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
     * @version 2.0
     * @note discovery
     * @datetime 2021-08-19 15:23
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function discoveryIndex(Request $request)
    {
        $longtitude = $request->input('longtitude', 0);
        $latitude = $request->input('latitude', 0);
        $location = array($longtitude, $latitude);
        
        if($longtitude == 0 || $latitude == 0) {
            return $this->discoveryIndexOld($request);
        }

        $deliveryUsers = app(UserRepository::class)
            ->allWithBuilder()
            ->join('shops_addresses', 'users.user_id', '=', 'shops_addresses.shop_id')
            ->where('user_activation' , 1)
            ->where('user_shop' , 1)
            ->where('user_verified' , 1)
            ->where('user_delivery' , 1)
            ->orderByRaw("(sqrt(power(`t_shops_addresses`.`longitude`-$location[0], 2) + power(`t_shops_addresses`.`latitude`-$location[1], 2)))")
            // ->orderByDesc('user_created_at')
            ->select(['user_id' , 'user_name' , 'user_nick_name' , 'user_avatar' , 'user_delivery' , 'user_shop' , 'user_bg' , 'user_address' ,
                'shops_addresses.longitude', 'shops_addresses.latitude'])
            ->paginate(10);
        $deliveryUsers->each(function($deliveryUser) use ($location){
            $deliveryUser->userPoint = app(UserRepository::class)->findPointByUserId($deliveryUser->user_id);
        
            $deliveryTime = 0.0;
            if(is_numeric($deliveryUser->longitude) && is_numeric($deliveryUser->latitude)) 
            {
                $dt = $this->deliveryTime($location, 
                    array(number_format($deliveryUser->longitude, 10), number_format($deliveryUser->latitude, 10)));
                if(!is_nan($dt) && !is_infinite($dt)) {
                    $deliveryTime = $dt;
                }
            }

            $deliveryUser->deliveryTime = $deliveryTime;
        });
        
        $result = UserCollection::collection($deliveryUsers);
        // Log::info('discoveryIndex', array('result' => $result));
        return $result; 
    }

    public function discoveryIndexOld(Request $request)
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
        $result = UserCollection::collection($deliveryUsers);
        // Log::info('discoveryIndex', array('result' => $result));
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
        $sort = (string)$request->input('sort', 'desc');
        $appends['type'] = $type;
        $appends['order'] = $order;
        $appends['tag'] = $tag;
        $appends['sort'] = $sort;
        $pageName = 'page';
        $page     = (int)$request->input($pageName, 1);
        $perPage  = (int)$request->input('per_page', 10);
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
            return isset($v['shop_id'], $v['start']) && count($v['start']) === 2;
        } , ARRAY_FILTER_USE_BOTH);
        $shopIds = array_column($location , 'shop_id');
        $addresses = DB::table('shops_addresses')->whereIn('shop_id' , $shopIds)->get();
        $location = array_map(function($v) use ($addresses){
            $address = $addresses->where('shop_id' , $v['shop_id'])->first();
            return array(
                'shop_id'=>$v['shop_id'],
                'start'=>$v['start'],
                'end'=>empty($address)?$v['start']:array((float)$address->longitude, (float)$address->latitude),
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
        $defaultDeliveryCost = config('common.default_delivery_cost');
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
                    if(!isset($routes['routes'][0], $routes['waypoints']))
                    {
                        abort(500 , 'The result is abnormal!');
                    }
                    $route = $routes['routes'][0];
                    $waypoints = $routes['waypoints'];
                    $distance = $route['distance'];
                    switch ($distance)
                    {
                        case $distance>6000&&$distance<=12000:
                            $deliveryCost=18;
                            break;
                        case $distance>12000:
                            $deliveryCost=25;
                            break;
                        default:
                            $deliveryCost=15;
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
                'rejected' => function ($reason, $index) use ($location , $user , &$distances , $defaultDeliveryCost){
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
                        'delivery_cost'=>$defaultDeliveryCost,
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
                    'delivery_cost'=>$defaultDeliveryCost,
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

    public function bitrixOrderCallback(Request $request)
    {
        Log::info(__FUNCTION__, $request->all());
        $type = (string)$request->input('type' , '');
        $id = $request->input('id' , '');
        $stage = (string)$request->input('stage' , '');
        $contactId = (string)$request->input('contact_id' , '');
        $userName = (string)$request->input('user_name' , '');
        $userContact = (string)$request->input('user_contact' , '');
        $userAddress = (string)$request->input('user_address' , '');
        $responsible = (string)$request->input('responsible' , '');
        $now = date('Y-m-d H:i:s');
        if($type=='new'&&!empty($id)&&$stage=='New Order')
        {
            $bx24 = app('bitrix24');
            $deal = $bx24->getDeal($id);
            $products = $bx24->getDealProductRows($id);
            if(empty($deal['COMPANY_ID'])||empty($deal['CONTACT_ID'])||empty($contactId)||empty($userName)||empty($userContact)||empty($userAddress)||empty($products)||(string)$deal['CONTACT_ID']!==$contactId)
            {
                $bx24->deleteDeal($id);
                Log::info('delete_deal_1' , array(
                    'deal'=>$deal,
                    'data'=>$request->all(),
                ));
                return ;
            }
            $packagingFee = $deal['UF_CRM_1628733998830']??'';
            $packagingFree = $deal['UF_CRM_1628734031097']??'';
            $deliveryFee = $deal['UF_CRM_1628734060152']??'';
            $deliveryFree = $deal['UF_CRM_1628734075984']??'';
            $promoCode = (string)$deal['UF_CRM_1628735337461'];
            $companyId = (string)$deal['COMPANY_ID'];
            $currencyId = (string)$deal['CURRENCY_ID'];
            $comment = (string)$deal['COMMENTS'];
            $pay = money_to_number($deal['UF_CRM_1629103354670']);
            $purchasePrice = money_to_number($deal['UF_CRM_1628734746554']);
            $packagePurchase = money_to_number($deal['UF_CRM_1629098340599']);
            $brokeragePercentage = $deal['UF_CRM_1630463561'];
            $brokerage = ($purchasePrice+$packagePurchase)*$brokeragePercentage/100;
            $reason = $deal['UF_CRM_1630462597'];
            $grossProfit = $pay-($purchasePrice+$packagePurchase)*$brokeragePercentage/100;
            $income = ($purchasePrice+$packagePurchase)*0.05+money_to_number($deliveryFee);
            $shop = DB::table('bitrix_shops')->where('extension_id' , $companyId)->first();
            if(empty($shop))
            {
                $bx24->deleteDeal($id);
                Log::info('delete_deal_2' , array(
                    'deal'=>$deal,
                    'data'=>$request->all(),
                ));
                return ;
            }
            $gIds = collect($products)->pluck('PRODUCT_ID')->unique()->toArray();
            $productsQuantity = collect($products)->pluck('QUANTITY' , 'PRODUCT_ID')->toArray();
            $gs = Goods::whereIn('extension_id' , $gIds)->get();
            $shop = User::where('user_id' , $shop->user_id)->first();
            $sameShop = $gs->every(function($g , $k) use ($shop){
                return (int)$g->user_id === (int)$shop->user_id;
            });
            if(!$sameShop)
            {
                $bx24->deleteDeal($id);
                Log::info('delete_deal_3' , array(
                    '$gs'=>$gs->toArray(),
                    'deal'=>$deal,
                    'data'=>$request->all(),
                ));
                return ;
            }
            $gs->each(function ($g) use ($productsQuantity){
                $g->goodsNumber = $productsQuantity[$g->extension_id];
            });
            $code = PromoCode::where('promo_code' , $promoCode)->first();
            if(empty($code)||$code->limit<=0||empty($code->deadline)||$code->deadline<date('Y-m-d'))
            {
                $promoCode = '';
            }
            if(!empty($promoCode))
            {
                $reduction = $code->reduction;
                $discount = $code->percentage;
                $freeDelivery = (int)$code->free_delivery;
                $deliveryCoast = $code->free_delivery?0:money_to_number($deliveryFee);
            }else{
                $freeDelivery = (int)$deliveryFree;
                $deliveryCoast = $deliveryFree==="1"?0:money_to_number($deliveryFee);
            }
            $price = collect($gs)->sum(function ($shopG) {
                return $shopG->goodsNumber*$shopG->price;
            });
            $promoPrice = collect($gs)->sum(function ($shopG) {
                if($shopG->discounted_price<0)
                {
                    return $shopG->goodsNumber*$shopG->price;
                }
                return $shopG->goodsNumber*$shopG->discounted_price;
            });
            $packagingCost = $packagingFree==='1'?0:money_to_number($packagingFee);
            $orderId = app('snowflake')->id();
            $data = array(
                'order_id'=>$orderId,
                'user_id'=> 2055272474,
                'shop_id'=> $shop->user_id,
                'user_name'=>$userName,
                'user_contact'=>$userContact,
                'user_address'=>$userAddress,
                'detail'=>\json_encode($gs->toArray() , JSON_UNESCAPED_UNICODE),
                'order_price'=>round($price , 2),
                'promo_price'=>round($promoPrice , 2),
                'packaging_cost'=>round($packagingCost , 2),
                'currency'=>strtolower($currencyId)=='etb'?'BIRR':"USD",
                'comment'=>$comment,
                'resource'=>'bitrix',
                'operator'=>$responsible,
                'created_at'=>$now,
                'updated_at'=>$now,
            );
            $data['delivery_coast'] = $deliveryCoast;
            $data['promo_code'] = $promoCode;
            $data['free_delivery'] = $freeDelivery;
            $data['reduction'] = $reduction??0;
            $data['discount'] = $discount??0;
            if(!empty($promoCode))
            {
                $discount_type = (string)$code->discount_type;
                if($code->discount_type=='discount')
                {
                    $totalPrice = round($promoPrice*$code->percentage/100 , 2);
                    $discountedPrice = round($promoPrice*$code->percentage/100+$deliveryCoast+$packagingCost , 2);
                }else{
                    $totalPrice = round($promoPrice-$code->reduction , 2);
                    $discountedPrice = round($promoPrice-$code->reduction+$deliveryCoast+$packagingCost , 2);
                }
            }else{
                $totalPrice = round($promoPrice , 2);
                $discountedPrice = round($totalPrice+$deliveryCoast+$packagingCost , 2);
            }
            $data['discounted_price'] = $discountedPrice;
            $data['total_price'] = $totalPrice;
            $data['discount_type'] = $discount_type??'';
            if(!empty($data)) {
                try {
                    DB::beginTransaction();
                    $orderResult = DB::table('orders')->insert($data);
                    if (!$orderResult) {
                        abort('500', 'order insert failed!');
                    }
                    if(!empty($promoCode))
                    {
                        $codeResult = DB::table('promo_codes')->where('promo_code', $promoCode)->decrement('limit');
                        if ($codeResult <= 0) {
                            abort('500', 'promo code update failed!');
                        }
                    }
                    DB::table('bitrix_orders')->insert(array(
                        'order_id'=>$orderId,
                        'extension_id'=>$id,
                        'pay'=>$pay,
                        'purchase_price'=>$purchasePrice,
                        'package_purchase_price'=>$packagePurchase,
                        'gross_profit'=>$grossProfit,
                        'income'=>$income,
                        'brokerage_percentage'=>$brokeragePercentage,
                        'brokerage'=>$brokerage,
                        'reason'=>$reason,
                    ));
                    DB::commit();
                    $discounted = '';
                    if($data['discount_type']==='discount')
                    {
                        $discounted = (string)$data['discount'] . "%";
                    }elseif ($data['discount_type']=='reduction')
                    {
                        $discounted = $data['reduction'];
                    }
                    $bx24->updateDeal($id , array(
                        "TITLE"=>$orderId,
                        "SOURCE_ID"=>'call' ,
                        "SOURCE_DESCRIPTION"=>'call' ,
                        "UF_CRM_1628733612424"=>0, //Special price
                        "UF_CRM_1628733649125"=>$discountedPrice, //Shop discount price
                        "UF_CRM_1628733813318"=>$discounted, //Discount Used
                        "UF_CRM_1628733998830"=>$packagingCost,//Package fee
                        "UF_CRM_1628734031097"=>(bool)$data['packaging_cost'], //Package fee free？
                        "UF_CRM_1628734060152"=>$data['delivery_coast'], //Delivery fee
                        "UF_CRM_1628734075984"=>(bool)$data['free_delivery'], //Delivery fee free？
                        "UF_CRM_1628735337461"=>$promoCode, //Promo code
                        "UF_CRM_1629192007"=>$orderId,//ORDER_ID
                        "UF_CRM_1629271456064"=>$data['discounted_price'], //Total price Paid
                        "UF_CRM_1629274022921"=>45, //Platform type
                        "UF_CRM_1629461733965"=>$shop->user_nick_name, //Restaurant
                        "UF_CRM_1629555411"=>"0", //IS UPDATE
                        "UF_CRM_1629593448"=>"0", //IS FIELD UPDATE
                        "UF_CRM_1629103387129"=>(int)($pay>0), //Does the user pay?
                        "UF_CRM_1629103354670"=>$pay, //Fees paid by users
                        "UF_CRM_1630463379"=>$grossProfit, //Gross profit
                        "UF_CRM_1630463416"=>$income, //Income
                        "UF_CRM_1630463561"=>$brokeragePercentage, //Brokerage(%)
                        "UF_CRM_1630463595"=>$brokerage, //Brokerage(%)
                    ));
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::info('bitrix_order_update_fail', array(
                        'message' => $e->getMessage(),
                        'user_id' => 'callCenter',
                        'data' => $request->all(),
                        'deal' => $deal,
                    ));
                    $bx24->deleteDeal($id);
                }
            }
        }else if ($type=='update'){
            $bx24 = app('bitrix24');
            $deal = $bx24->getDeal($id);
            Log::info('update_deal' , $deal);
            $orderId = $deal['UF_CRM_1629192007']??'';
            $order = Order::where('order_id' , $orderId)->first();
            if(empty($order))
            {
                $bx24->deleteDeal($id);
                Log::info('delete_deal_4' , array(
                    'deal'=>$deal,
                    'data'=>$request->all(),
                ));
                return ;
            }
            $isFieldUpdate = (bool)($deal['UF_CRM_1629593448']??0);
            if(!$isFieldUpdate)
            {
                $stage = $request->get('stage', '');
                $schedule = 0;
                $isDispatch = false;
                switch ($stage) {
                    case "New Order":
                        $schedule = 1;
                        break;
                    case "Confirmed by Customer":
                        $schedule = 2;
                        break;
                    case "Sent To Driver":
                        $isDispatch = true;
                        $schedule = 3;
                        break;
                    case "Driver Accepted":
                    case "Driver Picked Up Food":
                        $schedule = 4;
                        break;
                    case "Order Completed":
                        $schedule = 5;
                        break;
                    case "Spam":
                        $schedule = 7;
                        break;
                    case "Not Receiving Phone call":
                        $schedule = 6;
                        break;
                    case "User Canceled Order":
                        $schedule = 8;
                        break;
                    case "Shop Canceled Order":
                        $schedule = 9;
                        break;
                    case "Other Reason":
                        $schedule = 10;
                        break;
                    default:
                        break;
                }
                if ($schedule > 0) {
                    $orderState = 0;
                    $time = date('Y-m-d H:i:s');
                    $schedule === 5 && $orderState = 1;
                    $schedule >= 6 && $orderState = 2;
                    $duration = (int)((strtotime($time) - strtotime($order->created_at)) / 60);
                    $data = ['status' => $orderState ?? 0, 'schedule' => $schedule, 'order_time' => $duration];
                    if ($schedule === 5) {
                        $data['delivered_at'] = $time;
                    }
                    $order = $order->toArray();
                    $order['id'] = Uuid::uuid1()->toString();
                    $order['updated_at'] = $time;
                    $data['operator'] = $order['operator'] = $responsible;
                    $isDispatch&&$data['assigned_at'] = $time;
                    $orderState===1&&$data['delivered_at'] = $time;
                    unset($order['format_price'], $order['format_discounted_price'], $order['format_promo_price'], $order['format_total_price'], $order['format_packaging_cost']);
                    $order['detail']  = \json_encode($order['detail'],JSON_UNESCAPED_UNICODE);
                    try {
                        DB::beginTransaction();
                        DB::table('orders')->where('order_id', $orderId)->update($data);
                        DB::table('orders_logs')->insert($order);
                        DB::commit();
                        if($isDispatch)
                        {
                            $this->dispatch((new ShipdayOrder($order))->onQueue('helloo_{ship_day_order}'));
                        }
                    }catch (\Exception $e)
                    {
                        DB::rollBack();
                        Log::error('order_update_fail', array(
                            'message'=>$e->getMessage(),
                            'data'=>$request->all(),
                        ));
                    }
                }
                return;
            }
            $lock_key = 'helloo:bitrix:repeat:order'.$id;
            $redis = new RedisList();
            if(!$redis->tryGetLock($lock_key))
            {
                return;
            }
            $packagingFee = $deal['UF_CRM_1628733998830']??'';
            $packagingFree = $deal['UF_CRM_1628734031097']??'';
            $deliveryFee = $deal['UF_CRM_1628734060152']??'';
            $deliveryFree = $deal['UF_CRM_1628734075984']??'';
            $currencyId = (string)$deal['CURRENCY_ID'];
            $comment = (string)$deal['COMMENTS'];
            $pay = money_to_number($deal['UF_CRM_1629103354670']);
            $purchasePrice = money_to_number($deal['UF_CRM_1628734746554']);
            $packagePurchase = money_to_number($deal['UF_CRM_1629098340599']);
            $brokeragePercentage = $deal['UF_CRM_1630463561'];
            $brokerage = ($purchasePrice+$packagePurchase)*$brokeragePercentage/100;
            $reason = $deal['UF_CRM_1630462597'];
            $grossProfit = $pay-($purchasePrice+$packagePurchase)*$brokeragePercentage/100;
            $income = ($purchasePrice+$packagePurchase)*0.05+money_to_number($deliveryFee);
            $products = $bx24->getDealProductRows($id);
            if(empty($deal['COMPANY_ID'])||empty($deal['CONTACT_ID'])||empty($contactId)||empty($userName)||empty($userContact)||empty($userAddress)||empty($products)||(string)$deal['CONTACT_ID']!==$contactId)
            {
                $bx24->deleteDeal($id);
                Log::info('delete_deal_5' , array(
                    'deal'=>$deal,
                    'data'=>$request->all(),
                    'products'=>$products,
                ));
                return ;
            }
            $companyId = (string)$deal['COMPANY_ID'];
            $bitrixShop = DB::table('bitrix_shops')->where('extension_id' , $companyId)->first();
            if(empty($bitrixShop))
            {
                $bx24->deleteDeal($id);
                $data = $order->toArray();
                $data['id'] = Uuid::uuid1()->toString();
                $data['updated_at'] = $now;
                unset($data['format_price'], $data['format_discounted_price'], $data['format_promo_price'], $data['format_total_price'], $data['format_packaging_cost']);
                DB::table('orders')->where('order_id' , $order->order_id)->delete();
                $data['detail'] = \json_encode($data['detail']);
                $data['operator'] = $responsible;
                DB::table('orders_logs')->insert($data);
                Log::info('delete_deal_6' , array(
                    'deal'=>$deal,
                    'data'=>$request->all(),
                ));
                return ;
            }
            $shop = User::where('user_id' , $bitrixShop->user_id)->first();
            $gIds = collect($products)->pluck('PRODUCT_ID')->unique()->toArray();
            $productsQuantity = collect($products)->pluck('QUANTITY' , 'PRODUCT_ID')->toArray();
            $gs = Goods::whereIn('extension_id' , $gIds)->get();
            $sameShop = $gs->every(function($g , $k) use ($order){
                return (int)$g->user_id === (int)$order->shop_id;
            });
            if(!$sameShop||(int)$shop->user_id!==(int)$order->shop_id)
            {
                $bx24->deleteDeal($id);
                $data = $order->toArray();
                $data['id'] = Uuid::uuid1()->toString();
                $data['updated_at'] = $now;
                DB::table('orders')->where('order_id' , $order->order_id)->delete();
                unset($data['format_price'], $data['format_discounted_price'], $data['format_promo_price'], $data['format_total_price'], $data['format_packaging_cost']);
                $data['detail'] = \json_encode($data['detail']);
                $data['operator'] = $responsible;
                DB::table('orders_logs')->insert($data);
                Log::info('delete_deal_7' , array(
                    '$sameShop'=>$sameShop,
                    '$shop->user_id'=>$shop->user_id,
                    '$order->shop_id'=>$order->shop_id,
                    'deal'=>$deal,
                    'data'=>$request->all(),
                    '$gs'=>$gs->toArray(),
                ));
                return ;
            }
            $orderDetail = collect($order->detail);
            $gs->each(function ($g) use ($productsQuantity , $orderDetail){
                $goods = $orderDetail->where('id' , $g->id)->first();
                $g->goodsNumber = $productsQuantity[$g->extension_id];
                if(isset($goods['specialPrice']))
                {
                    $g->specialPrice = $goods['specialPrice'];
                }
            });
            $promoCode = $order->promo_code;
            if(!empty($promoCode))
            {
                $reduction = $order->reduction;
                $discount = $order->percentage;
                $freeDelivery = (int)$order->free_delivery;
                $deliveryCoast = $order->free_delivery?0:money_to_number($deliveryFee);
            }else{
                $freeDelivery = (int)$deliveryFree;
                $deliveryCoast = $deliveryFree==="1"?0:money_to_number($deliveryFee);
            }
            $price = collect($gs)->sum(function ($shopG) {
                return $shopG->goodsNumber*$shopG->price;
            });
            $promoPrice = collect($gs)->sum(function ($shopG) {
                if(isset($shopG->specialPrice))
                {
                    return $shopG->goodsNumber*$shopG->specialPrice;
                }
                if($shopG->discounted_price<0)
                {
                    return $shopG->goodsNumber*$shopG->price;
                }
                return $shopG->goodsNumber*$shopG->discounted_price;
            });
            $packagingCost = $packagingFree==='1'?0:money_to_number($packagingFee);
            $orderId = $order->order_id;
            $data = array(
                'user_name'=>$userName,
                'user_contact'=>$userContact,
                'user_address'=>$userAddress,
                'detail'=>\json_encode($gs->toArray() , JSON_UNESCAPED_UNICODE),
                'order_price'=>round($price , 2),
                'promo_price'=>round($promoPrice , 2),
                'packaging_cost'=>round($packagingCost , 2),
                'currency'=>strtolower($currencyId)=='etb'?'BIRR':"USD",
                'comment'=>$comment,
                'operator'=>$responsible,
                'created_at'=>$now,
                'updated_at'=>$now,
            );
            $data['delivery_coast'] = $deliveryCoast;
            $data['promo_code'] = $promoCode;
            $data['free_delivery'] = $freeDelivery;
            $data['reduction'] = $reduction??0;
            $data['discount'] = $discount??0;
            if(!empty($promoCode))
            {
                $discount_type = (string)$order->discount_type;
                if($order->discount_type=='discount')
                {
                    $totalPrice = round($promoPrice*$order->percentage/100 , 2);
                    $discountedPrice = round($promoPrice*$order->percentage/100+$deliveryCoast+$packagingCost , 2);
                }else{
                    $totalPrice = round($promoPrice-$order->reduction , 2);
                    $discountedPrice = round($promoPrice-$order->reduction+$deliveryCoast+$packagingCost , 2);
                }
            }else{
                $totalPrice = round($promoPrice , 2);
                $discountedPrice = round($totalPrice+$deliveryCoast+$packagingCost , 2);
            }
            $data['discounted_price'] = $discountedPrice;
            $data['total_price'] = $totalPrice;
            $data['discount_type'] = $discount_type??'';
            $order = $order->toArray();
            $order['id'] = Uuid::uuid1()->toString();
            $order['updated_at'] = $now;
            unset($order['format_price'], $order['format_discounted_price'], $order['format_promo_price'], $order['format_total_price'], $order['format_packaging_cost']);
            $order['detail']  = \json_encode($order['detail'],JSON_UNESCAPED_UNICODE);
            $order['operator'] = $responsible;
            DB::table('orders_logs')->insert($order);
            DB::table('orders')->where('order_id' , $orderId)->update($data);
            DB::table('bitrix_orders')->where('order_id' , $orderId)->update(array(
                'extension_id'=>$id,
                'pay'=>$pay,
                'purchase_price'=>$purchasePrice,
                'package_purchase_price'=>$packagePurchase,
                'gross_profit'=>$grossProfit,
                'income'=>$income,
                'brokerage_percentage'=>$brokeragePercentage,
                'brokerage'=>$brokerage,
                'reason'=>$reason,
            ));
            $discounted = '';
            if($data['discount_type']==='discount')
            {
                $discounted = (string)$data['discount'] . "%";
            }elseif ($data['discount_type']=='reduction')
            {
                $discounted = $data['reduction'];
            }
            $deal = [
                "CONTACT_ID"=>$contactId,
                "UF_CRM_1628733649125"=>$discountedPrice, //Shop discount price
                "UF_CRM_1628733813318"=>$discounted, //Discount Used
                "UF_CRM_1628733998830"=>$data['packaging_cost'],//Package fee
                "UF_CRM_1628734031097"=>$packagingFree, //Package fee free？
                "UF_CRM_1628734060152"=>$data['delivery_coast'], //Delivery fee
                "UF_CRM_1628734075984"=>(int)$data['free_delivery'], //Delivery fee free？
                "UF_CRM_1628735337461"=>$data['promo_code'], //Promo code
                "UF_CRM_1629192007"=>$orderId,//ORDER_ID
                "UF_CRM_1629271456064"=>$data['discounted_price'], //Total price Paid
                "UF_CRM_1629461733965"=>$shop->user_nick_name, //Restaurant
                "UF_CRM_1629555411"=>'0', //IS UPDATE
                "UF_CRM_1629593448"=>'0', //IS FIELD UPDATE,
                "UF_CRM_1629103387129"=>(int)($pay>0), //Does the user pay?
                "UF_CRM_1629103354670"=>$pay, //Fees paid by users
                "UF_CRM_1630463379"=>$grossProfit, //Gross profit
                "UF_CRM_1630463416"=>$income, //Income
                "UF_CRM_1630463561"=>$brokeragePercentage, //Brokerage(%)
                "UF_CRM_1630463595"=>$brokerage, //Brokerage(%)
            ];
            Log::info('updateDeal' , $deal);
            $bx24->updateDeal($id , $deal);
        }
        return $this->response->created()->setStatusCode(200);
    }


    public function shipDayCallback(Request $request)
    {
        Log::info(__FUNCTION__ , $request->all());
        $event = (string)$request->input('event' , '');
        $shipOrder = (array)$request->input('order' , array());
        $carrier = $request->input('carrier' , '');
        $courier = $carrier['phone']??'';
        $schedule = 0;
        $bitrixSchedule = '';
        $stages = array('NEW' , 'PREPARATION', 'PREPAYMENT_INVOICE', '1' ,'2' ,'LOSE' ,'6' ,'5' ,'7' ,'APOLOGY' , 'WON');
        switch ($event){
            case "ORDER_ASSIGNED":
                //@todo Send To Driver
                //@todo Called Driver via Bitrix callback
                break;
            case "ORDER_ACCEPTED_AND_STARTED":
                //@todo Driver Accepted
                //@todo Called Shop
                $schedule = 4;
                $bitrixSchedule = '1';
                break;
            case "ORDER_PIKEDUP":
                //@todo Driver Picked Up Food
                //@todo Called Shop
                $schedule = 4;
                $bitrixSchedule = '2';
                break;
            case "ORDER_ONTHEWAY":
                //@todo
                //@todo Called Shop
//                $schedule = 4;
//                $bitrixSchedule = '2';
                break;
            case "ORDER_COMPLETED":
                //@todo Order Completed
                //@todo Delivered
                $schedule = 5;
                $bitrixSchedule = 'WON';
                break;
            case "ORDER_FAILED":
                //@todo Other Reason
                //@todo Other
                $schedule = 10;
                $bitrixSchedule = 'APOLOGY';
                break;
            default:
                break;
        }
        if($schedule>0)
        {
            $orderState = 0;
            $time = date('Y-m-d H:i:s');
            $schedule === 5 && $orderState = 1;
            $schedule >= 6 && $orderState = 2;
            $orderId = $shipOrder['order_number']??0;
            $order = Order::where('order_id', $orderId)->firstOrFail();
            $duration = (int)((strtotime($time) - strtotime($order->created_at)) / 60);
            $data = ['status' => $orderState ?? 0, 'schedule' => $schedule, 'order_time' => $duration];
            if ($schedule === 5) {
                $data['delivered_at'] = $time;
            }
            $order = $order->toArray();
            $order['id'] = Uuid::uuid1()->toString();
            $order['updated_at'] = $time;
            $data['courier'] = $courier;
            unset($order['format_price'], $order['format_discounted_price'], $order['format_promo_price'], $order['format_total_price'], $order['format_packaging_cost']);
            $order['detail']  = \json_encode($order['detail'],JSON_UNESCAPED_UNICODE);
            try {
                DB::beginTransaction();
                DB::table('orders')->where('order_id', $orderId)->update($data);
                DB::table('orders_logs')->insert($order);
                DB::commit();
            }catch (\Exception $e)
            {
                DB::rollBack();
                Log::error('order_update_fail', array(
                    'message'=>$e->getMessage(),
                    'data'=>$request->all(),
                ));
            }
        }
        if(!empty($bitrixSchedule))
        {
            $orderId = $shipOrder['order_number']??0;
            $bx24 = app('bitrix24');
            $bitrixOrder = DB::table('bitrix_orders')->where('order_id' , $orderId)->first();
            !empty($bitrixOrder)&&$bx24->updateDeal($bitrixOrder->extension_id , array(
                "STAGE_ID"=>$bitrixSchedule,
                "UF_CRM_1629555411"=>"0",
                "UF_CRM_1629593448"=>"0",
            ));
        }
    }

}
