<?php

namespace App\Http\Controllers\V1\Business;

use App\Models\Business\Goods;
use App\Models\Order;
use App\Repositories\Contracts\UserRepository;
use App\Resources\AnonymousCollection;
use App\Resources\OrderCollection;
use App\Resources\UserCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\V1\BaseController;
use App\Http\Requests\StoreDeliveryOrderRequest;
use Illuminate\Support\Facades\Redis;

class OrderController extends BaseController
{

    public function store(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $goods = (array)$request->input('goods');
        $userName = $request->input('user_name' , '');
        $userContact = $request->input('user_contact' , '');
        $userAddress = $request->input('user_address' , '');
        $key = "helloo:business:shopping_cart:service:account:".$userId;
        $cache = Redis::hmget($key , array_keys($goods));
        $cache = array_filter($cache , function ($v, $k){
            return !empty($v)&&!empty($k);
        } , ARRAY_FILTER_USE_BOTH);
        $filterGoods = array_filter($goods , function ($v, $k) use ($cache){
            return isset($cache[$k])&&$cache[$k]==$v;
        } , ARRAY_FILTER_USE_BOTH);
        if($goods!==$filterGoods)
        {
            abort(403 , 'An error occurred in the parameter!');
        }
        $gs = Goods::where('id' , array_keys($goods))->get();
        $shopGoods = $gs->reject(function ($g) {
            return $g->status==0;
        });
        $userIds = $shopGoods->pluck('user_id')->unique()->toArray();
        $users = app(UserRepository::class)->findByUserIds($userIds);
        $shopGoods = $shopGoods->groupBy('user_id')->toArray();
        $orderData = array();
        $returnData = array();
        $now = date('Y-m-d H:i:s');
        foreach ($shopGoods as $u=>$shopGs)
        {
            $orderId = app('snowflake')->id();
            $price = collect($shopGs)->sum(function ($shopG) use ($goods) {
                return $goods[$shopG['id']]*$shopG['price'];
            });
            $data = array(
                'order_id'=>$orderId,
                'user_id'=>$userId,
                'shop_id'=>$u,
                'user_name'=>$userName,
                'user_contact'=>$userContact,
                'user_address'=>$userAddress,
                'detail'=>\json_encode($shopGs , JSON_UNESCAPED_UNICODE),
                'order_price'=>round($price , 2),
                'created_at'=>$now,
                'updated_at'=>$now,
            );
            array_push($orderData , $data);
            $data['shop'] = new UserCollection($users->where('user_id' , $u)->first());
            array_push($returnData , $data);
        }
        !empty($orderData)&&DB::table('orders')->insert($orderData);
        return AnonymousCollection::collection($returnData);
    }

    public function preview(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $goods = (array)$request->input('goods');
        $key = "helloo:business:shopping_cart:service:account:".$userId;
        if(empty(array_keys($goods)))
        {
            abort(403 , 'Illegal request!');
        }
        $cache = Redis::hmget($key , array_keys($goods));
        $cache = array_combine(array_keys($goods) , $cache);
        $filterGoods = array_filter($cache , function ($v, $k){
            return !empty($v)&&!empty($k);
        } , ARRAY_FILTER_USE_BOTH);
        if(empty($filterGoods))
        {
            abort(403 , 'There is no goods in the shopping cart!');
        }
        $gs = Goods::where('id' , array_keys($filterGoods))->get();
        $shopGoods = $gs->reject(function ($g) {
            return $g->status==0;
        });
        $shopGoods->each(function($g) use ($filterGoods){
            $g->goodsNumber = $filterGoods[$g->id];
        });
        $userIds = $shopGoods->pluck('user_id')->toArray();
        $shops = app(UserRepository::class)->findByUserIds($userIds)->toArray();
        $shopGoods = collect($shopGoods->groupBy('user_id')->toArray());
        $phone = DB::table('users_phones')->where('user_id' , $user->user_id)->first();
        if(!empty($phone)&&$phone->user_phone_country=='251')
        {
            $currency = 'BIRR';
        }else
        {
            $currency = 'USD';
        }
        $returnData = array();
        foreach ($shops as $shop)
        {
            $shopGs = $shopGoods->get($shop['user_id']);
            $price = collect($shopGs)->sum(function ($shopG) {
                return $shopG['goodsNumber']*$shopG['price'];
            });
            array_push($returnData , array(
                'user'=>new UserCollection(collect($shop)->only('user_id' , 'user_name' , 'user_nick_name' , 'user_avatar_link')),
                'goods'=>$shopGoods->get($shop['user_id']),
                'subTotal'=>$price,
                'deliveryCoast'=>30,
                'currency'=>$currency,
            ));
        }
        return AnonymousCollection::collection(collect($returnData));
    }

    public function my(Request $request)
    {
        $appends = array();
        $userId = auth()->id();
        $type = $request->input('type' , 'progress');
        $appends['type'] = $type;
        if($type=='progress')
        {
            $orders = Order::where('user_id' , $userId)->where('status' , 0)->orderByDesc('created_at')->limit(20)->get();
        }elseif($type=='completed')
        {
            $orders = Order::where('user_id' , $userId)->where('status' , 1)->orderByDesc('created_at')->paginate(10)->appends($appends);
        }elseif($type=='canceled')
        {
            $orders = Order::where('user_id' , $userId)->where('status' , 2)->orderByDesc('created_at')->paginate(10)->appends($appends);
        }else{
            $orders = collect();
        }
        return OrderCollection::collection($orders);
    }
}
