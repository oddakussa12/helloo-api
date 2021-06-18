<?php

namespace App\Http\Controllers\V1\Business;

use App\Models\Goods;
use App\Models\Order;
use App\Repositories\Contracts\UserRepository;
use App\Resources\AnonymousCollection;
use App\Resources\OrderCollection;
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
        $shopGoods = $shopGoods->groupBy('user_id')->toArray();
        $data = array();
        $now = date('Y-m-d H:i:s');
        foreach ($shopGoods as $u=>$shopGs)
        {
            $orderId = app('snowflake')->id();
            $price = collect($shopGs)->sum(function ($shopG) use ($goods) {
                return $goods[$shopG['id']]*$shopG['price'];
            });
            array_push($data , array(
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
            ));
        }
        !empty($data)&&DB::table('orders')->insert($data);
        return $this->response->created();
    }

    public function preview(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $goods = (array)$request->input('goods');
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
        $shopGoods->each(function($g) use ($goods){
            $g->goodsNumber = $goods[$g->id];
        });
        $userIds = $shopGoods->pluck('user_id')->toArray();
        $shopGoods = collect($shopGoods->groupBy('user_id')->toArray());
        $shops = collect(UserCollection::collection(app(UserRepository::class)->findByUserIds($userIds)));
        $shops->each(function($shop) use ($shopGoods){
            $shop->put('goods' , AnonymousCollection::collection($shopGoods->get($shop->get('user_id'))));
        });
        return AnonymousCollection::collection($shops);
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
