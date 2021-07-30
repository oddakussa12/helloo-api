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
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Resources\OrderCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Resources\AnonymousCollection;
use App\Http\Controllers\V1\BaseController;
use App\Repositories\Contracts\UserRepository;

class OrderController extends BaseController
{
    /**
     * @note 下单
     * @datetime 2021-07-12 17:56
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|void
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $jti = JWTAuth::getClaim('jti');
        $deliveryCoast = strval($request->input('delivery_coast' , ''));
        $plaintext = opensslDecryptV2($deliveryCoast , $jti);
        $deliveryCoasts = \json_decode($plaintext , true);
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
        $discounted = boolval(Redis::get("helloo:business:order:service:discounted:switch"));
        $goodsCount = $shopGoods->count();
        if(!empty($promoCode)&&$goodsCount==1)
        {
            $code = PromoCode::where('promo_code' , $promoCode)->first();
            if(empty($code))
            {
                abort(422 , 'The promo code does not exist!');
            }
            if(!empty($code)&&$code->limit<=0)
            {
                abort(422 , 'The promo code has been used up!');
            }
            if(!empty($code)&&!empty($code->deadline)&&$code->deadline<date('Y-m-d'))
            {
                abort(422 , 'The promo code has expired!');
            }
            if(!$discounted&&!empty($code))
            {
                $discountedGoods = $gs->reject(function ($g) {
                    return $g->discounted_price<0;
                });
                $discountedGoodsIds = $discountedGoods->pluck('user_id')->unique()->toArray();
                if(!empty($discountedGoodsIds))
                {
                    abort(422 , 'The conditions for using promotional codes are not met!');
                }
            }
        }
        $shopGoods->each(function($g) use ($goods){
            $g->goodsNumber = intval($goods[$g->id]);
        });
        $goodsIds = $shopGoods->pluck('id')->toArray();
        $userIds = $shopGoods->pluck('user_id')->unique()->toArray();
        if(count($userIds)>3)
        {
            abort(403 , 'At most 3 orders can be placed at the same time!');
        }
        if(is_array($deliveryCoasts))
        {
            foreach ($deliveryCoasts as $k=>$v)
            {
                if(!in_array($k , $userIds)||!isset($v['distance'])||!isset($v['delivery_cost'])||!isset($v['start'][0])||!isset($v['start'][1])||!isset($v['end'][0])||!isset($v['end'][1]))
                {
                    abort(422 , 'Illegal delivery coast format!');
                }
            }
        }
        $users = app(UserRepository::class)->findByUserIds($userIds);
        $phones = DB::table('users_phones')->whereIn('user_id' , $userIds)->get()->pluck('user_phone_country' , 'user_id')->toArray();
        $shopGoods = $shopGoods->groupBy('user_id')->toArray();
        $orderData = array();
        $returnData = array();
        $brokerage_percentage = 95;
        $now = date('Y-m-d H:i:s');
        $firstKey = "helloo:business:order:service:first";
        $orderNumber = count($shopGoods);
        $orderAddresses = array();
        foreach ($shopGoods as $u=>$shopGs)
        {
            $orderId = app('snowflake')->id();
            if(is_array($deliveryCoasts))
            {
                array_push($orderAddresses , array(
                    'id'=>app('snowflake')->id(),
                    'user_id'=>$userId,
                    'order_id'=>$orderId,
                    'user_latitude'=>$deliveryCoasts[$u]['start'][0],
                    'user_longitude'=>$deliveryCoasts[$u]['start'][1],
                    'shop_latitude'=>$deliveryCoasts[$u]['end'][0],
                    'shop_longitude'=>$deliveryCoasts[$u]['end'][1],
                    'created_at'=>$now,
                ));
            }
            $price = collect($shopGs)->sum(function ($shopG) use ($goods) {
                return $goods[$shopG['id']]*$shopG['price'];
            });
            $promoPrice = collect($shopGs)->sum(function ($shopG) use ($goods) {
                if($shopG['discounted_price']<0)
                {
                    return $goods[$shopG['id']]*$shopG['price'];
                }
                return $goods[$shopG['id']]*$shopG['discounted_price'];
            });
            $packagingCost = collect($shopGs)->sum(function ($shopG) {
                return $shopG['goodsNumber']*$shopG['packaging_cost'];
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
                'promo_price'=>round($promoPrice , 2),
                'packaging_cost'=>round($packagingCost , 2),
                'currency'=>$currency,
                'created_at'=>$now,
                'updated_at'=>$now,
            );
            if(!empty($code)&&$code->limit>0)
            {
                if($code->free_delivery)
                {
                    $deliveryCoast = 0;
                }else{
                    $deliveryCoast = $deliveryCoasts===null?100:((isset($deliveryCoasts[$u]['delivery_cost']))?round(floatval($deliveryCoasts[$u]['delivery_cost']) , 2):100);
                }
                $data['delivery_coast'] = $deliveryCoast;
                $data['promo_code'] = $code->promo_code;
                $data['free_delivery'] = intval($code->free_delivery);
                $data['reduction'] = $code->reduction;
                $data['discount'] = $code->percentage;
                $discount_type = strval($code->discount_type);
                if($code->discount_type=='discount')
                {
                    $totalPrice = round($promoPrice*$code->percentage/100 , 2);
                    $discountedPrice = round($promoPrice*$code->percentage/100+$deliveryCoast , 2);
                }else{
                    $totalPrice = round($promoPrice-$code->reduction , 2);
                    $discountedPrice = round($promoPrice-$code->reduction+$deliveryCoast , 2);
                }
            }else{
                $deliveryCoast = $deliveryCoasts===null?100:((isset($deliveryCoasts[$u]['delivery_cost']))?round(floatval($deliveryCoasts[$u]['delivery_cost']) , 2):100);
                $discount_type = '';
                $data['delivery_coast'] = $deliveryCoast;
                $data['promo_code'] = '';
                $data['free_delivery'] = 0;
                $data['reduction'] = 0;
                $data['discount'] = 100;
                $totalPrice = round($promoPrice , 2);
                $discountedPrice = round($promoPrice+$deliveryCoast , 2);
            }
            if($orderNumber==1)
            {
                $discount = round(floatval(Redis::get('helloo:business:order:service:first:discount')) , 2);
                if($discount>0)
                {
                    $r = Redis::sadd($firstKey , $user->user_id);
                    if($r)
                    {
                        $firstTotal = $totalPrice-$discount;
                        $totalPrice = $firstTotal<0?0:$totalPrice;
                        $firstDiscount = $discountedPrice-$discount;
                        $discountedPrice = $firstDiscount<0?0:$firstDiscount;
                        $data['first_order'] = $discount;
                    }
                }
            }
            $data['discounted_price'] = $discountedPrice;
            $data['total_price'] = $totalPrice;
            $data['discount_type'] = $discount_type;
            $data['brokerage_percentage'] = $brokerage_percentage;
            $brokerage = round($brokerage_percentage/100*$price , 2);
            $data['brokerage'] = $brokerage;
            $data['profit'] = round($data['discounted_price']-$brokerage , 2);
            array_push($orderData , $data);
            $user = $users->where('user_id' , $u)->first()->only('user_id' , 'user_name' , 'user_nick_name' , 'user_avatar_link' , 'user_contact' , 'user_address');
            $data['shop'] = new UserCollection($user);
            $data['detail'] = $shopGs;
            unset($data['discount_type'] , $data['brokerage_percentage'] , $data['brokerage'] , $data['profit']);
            $data['free_delivery'] = boolval($data['free_delivery']);
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
                if(!empty($orderAddresses))
                {
                    DB::table('orders_addresses')->insert($orderAddresses);
                }
                DB::commit();
            }catch (\Exception $e)
            {
                DB::rollBack();
                if(isset($r)&&$r)
                {
                    Redis::srem($firstKey , $user->user_id);
                }
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

    public function specialOrder(Request $request)
    {
        $goodsId = $request->input('goods_id');
        $userName = $request->input('user_name' , '');
        $userContact = $request->input('user_contact' , '');
        $userAddress = $request->input('user_address' , '');
        $jti = JWTAuth::getClaim('jti');
        $deliveryCoast = strval($request->input('delivery_coast' , ''));
        $plaintext = opensslDecryptV2($deliveryCoast , $jti);
        $deliveryCoasts = \json_decode($plaintext , true);
        $key = "helloo:business:goods:service:special:".$goodsId;
        $specialG = Redis::hgetall($key);
        if(empty($specialG))
        {
            DB::table('special_goods')->where('goods_id' , $goodsId)->update(array('status'=>0 , 'updated_at'=>date('Y-m-d H:i:s')));
            abort(403 , 'This goods is not a special offer!');
        }
        $specialGoods = DB::table('special_goods')->where('goods_id' , $goodsId)->first();
        if(empty($specialGoods)||$specialGoods->status==0)
        {
            Redis::del($key);
            abort(403 , 'This goods is not a special offer!');
        }
        $price = round($specialG['special_price'] , 2);
        $brokerage_percentage = 95;
        $user = auth()->user();
        $userId = $user->user_id;
        $orderId = app('snowflake')->id();
        $goods = Goods::where('id' , $goodsId)->firstOrFail();
        if(is_array($deliveryCoasts))
        {
            foreach ($deliveryCoasts as $k=>$v)
            {
                if(!in_array($k , array($goods->user_id))||!isset($v['distance'])||!isset($v['delivery_cost'])||!isset($v['start'][0])||!isset($v['start'][1])||!isset($v['end'][0])||!isset($v['end'][1]))
                {
                    abort(422 , 'Illegal delivery cost format!');
                }
            }
        }
        $orderPrice = $goods->price;
        $goods->specialPrice = $price;
        $goods->goodsNumber = 1;
        $now = date('Y-m-d H:i:s');
        $data = array(
            'order_id'=>$orderId,
            'user_id'=>strval($userId),
            'shop_id'=>strval($goods->user_id),
            'user_name'=>$userName,
            'user_contact'=>$userContact,
            'user_address'=>$userAddress,
            'discount_type'=>'special',
            'detail'=>\json_encode([$goods->toArray()] , JSON_UNESCAPED_UNICODE),
            'order_price'=>$orderPrice,
            'promo_price'=>$orderPrice,
            'packaging_cost'=>round($specialG['packaging_cost'] , 2),
            'currency'=>$goods->currency,
            'created_at'=>$now,
            'updated_at'=>$now,
        );
        $deliveryCoast = $deliveryCoasts===null?100:((isset($deliveryCoasts[$goods->user_id]['delivery_cost']))?round(floatval($deliveryCoasts[$goods->user_id]['delivery_cost']) , 2):100);
        $data['delivery_coast'] = empty($specialG['free_delivery'])?0:$deliveryCoast;
        $data['promo_code'] = '';
        $data['free_delivery'] = $specialG['free_delivery'];
        $data['reduction'] = 0;
        $data['discount'] = 100;
        $data['discounted_price'] = $price+$data['delivery_coast'];
        $data['total_price'] = $price;
        $data['brokerage_percentage'] = $brokerage_percentage;
        $brokerage = round($brokerage_percentage/100*$price , 2);
        $data['brokerage'] = $brokerage;
        $data['profit'] = round($data['discounted_price']-$brokerage , 2);
        $returnData = $data;
        $returnData['free_delivery'] = boolval($specialG['free_delivery']);
        $returnData['detail'] = new AnonymousCollection($goods);
        $returnData['shop'] = new UserCollection($user);
        DB::table('orders')->insert($data);
        unset($returnData['discount_type'] , $returnData['brokerage_percentage'] , $returnData['brokerage'] , $returnData['profit']);
        $data['free_delivery'] = boolval($data['free_delivery']);
        return new AnonymousCollection(collect($returnData));
    }

    /**
     * @note 订单预览
     * @datetime 2021-07-12 17:57
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function preview(Request $request)
    {
        $user = auth()->user();
        $jti = JWTAuth::getClaim('jti');
        $userId = $user->user_id;
        $goods = (array)$request->input('goods');
        $deliveryCoast = strval($request->input('delivery_coast' , ''));
        $plaintext = opensslDecryptV2($deliveryCoast , $jti);
        $deliveryCoasts = \json_decode($plaintext , true);
        $promoCode = strval($request->input('promo_code' , ''));
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
        $userIds = $shopGoods->pluck('user_id')->unique()->toArray();
        if(count($userIds)>3)
        {
            abort(403 , 'At most 3 orders can be placed at the same time!');
        }
        if(is_array($deliveryCoasts))
        {
            foreach ($deliveryCoasts as $k=>$v)
            {
                if(!in_array($k , $userIds)||!isset($v['distance'])||!isset($v['delivery_cost'])||!isset($v['start'][0])||!isset($v['start'][1])||!isset($v['end'][0])||!isset($v['end'][1]))
                {
                    abort(422 , 'Illegal delivery coast format!');
                }
            }
        }
        $discounted = boolval(Redis::get("helloo:business:order:service:discounted"));
        $goodsCount = $shopGoods->count();
        $flag = $status = 0;
        $message = '';
        if(!empty($promoCode)&&$goodsCount==1)
        {
            $code = PromoCode::where('promo_code' , $promoCode)->first();
            if(empty($code)||$code->limit<=0||(!empty($code->deadline)&&$code->deadline<date('Y-m-d')))
            {
                $flag = 1;
                $message = "Sorry this code is invalid!";
            }
            if(!$discounted&&!empty($code))
            {
                $discountedGoods = $gs->reject(function ($g) {
                    return $g->discounted_price<0;
                });
                $discountedGoodsIds = $discountedGoods->pluck('user_id')->unique()->toArray();
                if(!empty($discountedGoodsIds))
                {
                    $flag = 2;
                    $message = "The conditions for using promotional codes are not met!";
                }
            }
        }
        $shopGoods->each(function($g) use ($filterGoods){
            $g->goodsNumber = intval($filterGoods[$g->id]);
        });
        $userIds = $shopGoods->pluck('user_id')->unique()->toArray();
        $orderNumber = count($userIds);
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
            $promoPrice = collect($shopGs)->sum(function ($shopG) {
                if($shopG['discounted_price']<0)
                {
                    return $shopG['goodsNumber']*$shopG['price'];
                }
                return $shopG['goodsNumber']*$shopG['discounted_price'];
            });
            $packagingCost = collect($shopGs)->sum(function ($shopG) {
                return $shopG['goodsNumber']*$shopG['packaging_cost'];
            });
            $currency = isset($phones[$shop['user_id']])&&$phones[$shop['user_id']]=='251'?'BIRR':"USD";
            $data['currency'] = $currency;
            if(!empty($code)&&$flag==0)
            {
                $status = 200;
                $message = '20% off for all products over BIRR300!';
                $deliveryCoast = $code->free_delivery?0:$deliveryCoasts===null?100:((isset($deliveryCoasts[$shop['user_id']]['delivery_cost']))?round(floatval($deliveryCoasts[$shop['user_id']]['delivery_cost']) , 2):100);
                $data['deliveryCoast'] = $deliveryCoast;
                if($code->discount_type=='discount')
                {
                    $totalPrice = round($promoPrice*$code->percentage/100 , 2);
                }else{
                    $totalPrice = round($promoPrice-$code->reduction , 2);
                }
            }else{
                $deliveryCoast = $deliveryCoasts===null?100:((isset($deliveryCoasts[$shop['user_id']]['delivery_cost']))?round(floatval($deliveryCoasts[$shop['user_id']]['delivery_cost']) , 2):100);
                $totalPrice = round($promoPrice , 2);
            }
            if($orderNumber==1)
            {
                $discount = round(floatval(Redis::get('helloo:business:order:service:first:discount')) , 2);
                if($discount>0)
                {
                    $firstKey = "helloo:business:order:service:first";
                    $r = Redis::sismember($firstKey , $user->user_id);
                    if($r)
                    {
                        $firstTotal = $totalPrice-$discount;
                        $totalPrice = $firstTotal<0?0:$totalPrice;
                    }
                }
            }
            array_push($returnData , array_merge($data , array(
                'shop'=>new UserCollection(collect($shop)->only('user_id' , 'user_name' , 'user_nick_name' , 'user_avatar_link' , 'user_contact' , 'user_address')),
                'goods'=>$shopGs,
                'subTotal'=>$price,
                'subDiscountedTotal'=>$totalPrice,
                'deliveryCoast'=>$deliveryCoast,
                'packagingCost'=>$packagingCost,
            )));
        }
        return $this->response->array(
            array('data'=>AnonymousCollection::collection(collect($returnData)) , 'message'=>$message , 'code'=>$status)
        );
    }

    /**
     * @note 我的订单
     * @datetime 2021-07-12 17:57
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function my(Request $request)
    {
        $userId = auth()->id();
        $orders = Order::where('user_id' , $userId)->orderByDesc('created_at')->paginate(10);
        $shopIds = $orders->pluck('shop_id')->unique()->toArray();
        $shops = app(UserRepository::class)->findByUserIds($shopIds);
        $orders->each(function($order) use ($shops){
            $order->shop = new UserCollection($shops->where('user_id' , $order->shop_id)->first()->only('user_id' , 'user_name' , 'user_nick_name' , 'user_avatar_link' , 'user_contact' , 'user_address'));
        });
        return OrderCollection::collection($orders);
    }

    /**
     * @note 订单详情
     * @datetime 2021-07-12 17:57
     * @param Request $request
     * @param $id
     * @return OrderCollection
     */
    public function show(Request $request , $id)
    {
        $order = Order::where('order_id' , $id)->first();
        if(empty($order))
        {
            abort(404 , 'Sorry, the order does not exist!');
        }
        $shop = app(UserRepository::class)->findByUserId($order->shop_id)->only('user_id' , 'user_name' , 'user_nick_name' , 'user_avatar_link' , 'user_contact' , 'user_address');
        $order->shop = new UserCollection($shop);
        return new OrderCollection($order);
    }
}
