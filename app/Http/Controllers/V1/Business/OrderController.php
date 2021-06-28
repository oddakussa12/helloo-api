<?php

namespace App\Http\Controllers\V1\Business;

use App\Jobs\OrderSms;
use Illuminate\Http\Request;
use App\Models\Business\Goods;
use App\Models\Business\Order;
use App\Resources\UserCollection;
use App\Models\Business\PromoCode;
use App\Jobs\ShoppingCartTransfer;
use App\Jobs\OrderSynchronization;
use App\Resources\OrderCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Resources\AnonymousCollection;
use App\Http\Controllers\V1\BaseController;
use App\Repositories\Contracts\UserRepository;

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
        $promoCode = $request->input('promo_code' , '');
        $key = "helloo:business:shopping_cart:service:account:".$userId;
        if(empty(array_keys($goods)))
        {
            abort(422);
        }
        $cache = Redis::hmget($key , array_keys($goods));
        $cache = array_combine(array_keys($goods) , $cache);
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
        $gs = Goods::whereIn('id' , array_keys($goods))->get();
        $shopGoods = $gs->reject(function ($g) {
            return $g->status==0;
        });
        if(!empty($promoCode)&&$shopGoods->count()==1)
        {
            $code = PromoCode::where('promo_code' , $promoCode)->first();
            if(!empty($code)&&$code->limit<=0)
            {
                abort(422 , 'The promo code has been used up!');
            }
        }
        $shopGoods->each(function($g) use ($goods){
            $g->goodsNumber = intval($goods[$g->id]);
        });
        $goodsIds = $shopGoods->pluck('id')->toArray();
        $userIds = $shopGoods->pluck('user_id')->unique()->toArray();
        $users = app(UserRepository::class)->findByUserIds($userIds);
        $phones = DB::table('users_phones')->whereIn('user_id' , $userIds)->get()->pluck('user_phone_country' , 'user_id')->toArray();
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
            $currency = isset($phones[$u])&&$phones[$u]=='251'?'BIRR':"USD";
            $data = array(
                'order_id'=>$orderId,
                'user_id'=>strval($userId),
                'shop_id'=>strval($u),
                'user_name'=>$userName,
                'user_contact'=>$userContact,
                'user_address'=>$userAddress,
                'detail'=>\json_encode($shopGs , JSON_UNESCAPED_UNICODE),
                'order_price'=>round($price , 2),
                'currency'=>$currency,
                'created_at'=>$now,
                'updated_at'=>$now,
            );
            if(!empty($code)&&$code->limit>0)
            {
                $deliveryCoast = $code->free_delivery?0:30;
                $data['delivery_coast'] = $deliveryCoast;
                $data['promo_code'] = $code->promo_code;
                $data['free_delivery'] = intval($code->free_delivery);
                $data['reduction'] = $code->reduction;
                $data['discount'] = $code->percentage;
                if($code->discount_type=='discount')
                {
                    $discountedPrice = round($price*$code->percentage/100+$deliveryCoast , 2);
                }else{
                    $discountedPrice = round($price-$code->reduction+$deliveryCoast , 2);
                }
                $data['discounted_price'] = $discountedPrice;
            }else{
                $deliveryCoast = 30;
                $data['delivery_coast'] = $deliveryCoast;
                $data['discounted_price'] = round($price+$deliveryCoast , 2);
            }
            array_push($orderData , $data);
            $user = $users->where('user_id' , $u)->first()->only('user_id' , 'user_name' , 'user_nick_name' , 'user_avatar_link' , 'user_contact' , 'user_address');
            $data['shop'] = new UserCollection($user);
            $data['detail'] = $shopGs;
            array_push($returnData , $data);
        }
        if(!empty($orderData))
        {
            try{
                DB::beginTransaction();
                $orderResult = DB::table('orders')->insert($orderData);
                if(!$orderResult)
                {
                    abort('500' , 'order insert failed!');
                }
                if(!empty($code)&&$code->limit>0)
                {
                    $codeResult = DB::table('promo_codes')->where('promo_code' , $promoCode)->decrement('limit');
                    if($codeResult<=0)
                    {
                        abort('500' , 'promo code update failed!');
                    }
                }
                DB::commit();
            }catch (\Exception $e)
            {
                DB::rollBack();
                Log::info('order_store_fail' , array(
                    'message'=>$e->getMessage(),
                    'user_id'=>$userId,
                    'data'=>$request->all()
                ));
                return $this->response->error('Order creation failed!' , 424);
            }
            OrderSynchronization::dispatch($returnData)->onQueue('helloo_{order_synchronization}');
            Redis::hdel($key , $goodsIds);
            ShoppingCartTransfer::dispatch($userId , $goodsIds)->onQueue('helloo_{shopping_cart_transfer}');
            OrderSms::dispatch($orderData , 'batch')->onQueue('helloo_{delivery_order_sms}');
        }
        return AnonymousCollection::collection(collect($returnData));
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
        $gs = Goods::whereIn('id' , array_keys($filterGoods))->get();
        $shopGoods = $gs->reject(function ($g) {
            return $g->status==0;
        });
        $shopGoods->each(function($g) use ($filterGoods){
            $g->goodsNumber = intval($filterGoods[$g->id]);
        });
        $userIds = $shopGoods->pluck('user_id')->unique()->toArray();
        $phones = DB::table('users_phones')->whereIn('user_id' , $userIds)->get()->pluck('user_phone_country' , 'user_id')->toArray();
        $shopGoods = collect($shopGoods->groupBy('user_id')->toArray());
        $shops = app(UserRepository::class)->findByUserIds($userIds)->toArray();
        $returnData = array();
        foreach ($shops as $shop)
        {
            $shopGs = $shopGoods->get($shop['user_id']);
            $price = collect($shopGs)->sum(function ($shopG) {
                return $shopG['goodsNumber']*$shopG['price'];
            });
            $currency = isset($phones[$shop['user_id']])&&$phones[$shop['user_id']]=='251'?'BIRR':"USD";
            array_push($returnData , array(
                'shop'=>new UserCollection(collect($shop)->only('user_id' , 'user_name' , 'user_nick_name' , 'user_avatar_link' , 'user_contact' , 'user_address')),
                'goods'=>$shopGs,
                'subTotal'=>$price,
                'deliveryCoast'=>30,
                'currency'=>$currency,
            ));
        }
        return AnonymousCollection::collection(collect($returnData));
    }

    public function my(Request $request)
    {
        $userId = auth()->id();
        $orders = Order::where('user_id' , $userId)->orderByDesc('created_at')->paginate(10);
        $shopIds = $orders->pluck('shop_id')->unique()->toArray();
        $shops = app(UserRepository::class)->findByUserIds($shopIds);
        $orders->each(function($order) use ($shops){
            $order->shop = new UserCollection($shops->where('user_id' , $order->shop_id)->first()->only('user_id' , 'user_name' , 'user_nick_name' , 'user_avatar_link' , 'user_contact' , 'user_address'));
            $order->delivery_coast = 30;
        });
        return OrderCollection::collection($orders);
    }

    public function show(Request $request , $id)
    {
        $order = Order::where('order_id' , $id)->first();
        if(empty($order))
        {
            abort(404 , 'Sorry, the order does not exist!');
        }
        $shop = app(UserRepository::class)->findByUserId($order->shop_id)->only('user_id' , 'user_name' , 'user_nick_name' , 'user_avatar_link' , 'user_contact' , 'user_address');
        $order->delivery_coast = 30;
        $order->shop = new UserCollection($shop);
        return new OrderCollection($order);
    }
}
