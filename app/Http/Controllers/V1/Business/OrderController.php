<?php

namespace App\Http\Controllers\V1\Business;

use App\Jobs\OrderSms;
use App\Traits\BotOrder;
use App\Jobs\Bitrix24Order;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use App\Models\Business\Goods;
use App\Models\Business\Order;
use App\Resources\UserCollection;
use Tymon\JWTAuth\Facades\JWTAuth;
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
    use BotOrder;

    /**
     * @note 下单
     * @datetime 2021-07-12 17:56
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|void
     */
    public function store(Request $request)
    {
        $agent = new Agent();
        if($agent->match('HellooBot'))
        {
            return $this->botStore($request);
        }
        $type = $request->input('type' , '');
        if($type=='special')
        {
            return $this->specialStore($request);
        }
        $promoCode = $request->input('promo_code' , '');
        if(!empty($promoCode))
        {
            return $this->promoStore($request);
        }
        return $this->normalStore($request);
    }

    /**
     * @note 正常下单
     * @datetime 2021-08-03 15:18
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|void
     */
    private function normalStore(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $jti = JWTAuth::getClaim('jti');
        $deliveryCoast = (string)$request->input('delivery_coast', '');
        $plaintext = opensslDecryptV2($deliveryCoast , $jti);
        $deliveryCoasts = \json_decode($plaintext , true);
        $goods = (array)$request->input('goods');
        $userName = $request->input('user_name' , '');
        $userContact = $request->input('user_contact' , '');
        $userAddress = $request->input('user_address' , '');
        $key = "helloo:business:shopping_cart:service:account:".$userId;
        $gIds = array_keys($goods);
        if(empty($gIds))
        {
            abort(422);
        }
        $cache = Redis::hmget($key , $gIds);
        $cache = array_map(function ($v){
            return (int)$v;
        } , $cache);
        $cache = array_combine($gIds , $cache);
        $cache = array_filter($cache , function ($v, $k){
            return !empty($v)&&!empty($k);
        } , ARRAY_FILTER_USE_BOTH);
        array_walk($goods, function($value, $key) use (&$goods ){
            $goods[$key] = (int)$value;
        });
        $filterGoods = array_filter($goods , function ($v, $k) use ($cache){
            return isset($cache[$k])&&$cache[$k]===$v;
        } , ARRAY_FILTER_USE_BOTH);
        if($goods!==$filterGoods)
        {
            abort(403 , 'An error occurred in the parameter!');
        }
        $gs = Goods::whereIn('id' , array_keys($goods))->get();
        $shopGoods = $gs->reject(function ($g) {
            return $g->status===0;
        });
        $shopGoods->each(function($g) use ($goods){
            $g->goodsNumber = (int)$goods[$g->id];
        });
        $goodsIds = $shopGoods->pluck('id')->toArray();
        $userIds = $shopGoods->pluck('user_id')->unique()->toArray();
        if(count($userIds)>3||count($userIds)<=0)
        {
            abort(403 , 'At most 3 orders can be placed at the same time!');
        }
        if(is_array($deliveryCoasts))
        {
            foreach ($deliveryCoasts as $k=>$v)
            {
                if(!isset($v['distance'], $v['delivery_cost'], $v['start'][0], $v['start'][1], $v['end'][0], $v['end'][1]) || !in_array((string)$k, $userIds, true))
                {
                    abort(422 , 'Illegal delivery cost format!');
                }
            }
        }
        $users = app(UserRepository::class)->findByUserIds($userIds);
        $phones = DB::table('users_phones')->whereIn('user_id' , $userIds)->get()->pluck('user_phone_country' , 'user_id')->toArray();
        $shopGoods = $shopGoods->groupBy('user_id')->toArray();
        $orderData = array();
        $returnData = array();
        $now = date('Y-m-d H:i:s');
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
                    'user_longitude'=>$deliveryCoasts[$u]['start'][0],
                    'user_latitude'=>$deliveryCoasts[$u]['start'][1],
                    'shop_longitude'=>$deliveryCoasts[$u]['end'][0],
                    'shop_latitude'=>$deliveryCoasts[$u]['end'][1],
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
            $currency = isset($phones[$u])&&$phones[$u]==='251'?'BIRR':"USD";
            $data = array(
                'order_id'=>$orderId,
                'user_id'=> (string)$userId,
                'shop_id'=> (string)$u,
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
            $defaultDeliveryCost = config('common.default_delivery_cost');
            $deliveryCoast = !is_array($deliveryCoasts)?$defaultDeliveryCost:((isset($deliveryCoasts[$u]['delivery_cost']))?round((float)($deliveryCoasts[$u]['delivery_cost']) , 2):$defaultDeliveryCost);
            $discount_type = '';
            $data['delivery_coast'] = $deliveryCoast;
            $data['promo_code'] = '';
            $data['free_delivery'] = 0;
            $data['reduction'] = 0;
            $data['discount'] = 100;
            $totalPrice = round($promoPrice , 2);
            $discountedPrice = round($promoPrice+$deliveryCoast+$packagingCost , 2);
            $data['discounted_price'] = $discountedPrice;
            $data['total_price'] = $totalPrice;
            $data['discount_type'] = $discount_type;
            array_push($orderData , $data);
            $user = $users->where('user_id' , $u)->first()->only('user_id' , 'user_name' , 'user_nick_name' , 'user_avatar_link' , 'user_contact' , 'user_address');
            $data['shop'] = new UserCollection($user);
            $data['detail'] = $shopGs;
            unset($data['discount_type']);
            $data['free_delivery'] = (bool)$data['free_delivery'];
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
                if(!empty($orderAddresses))
                {
                    DB::table('orders_addresses')->insert($orderAddresses);
                }
                DB::commit();
            }catch (\Exception $e)
            {
                DB::rollBack();
                Log::error('normal_order_store_fail' , array(
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
            $this->dispatch((new Bitrix24Order($orderData , __FUNCTION__))->onQueue('helloo_{bitrix_order}'));
        }
        return OrderCollection::collection(collect($returnData));
    }

    /**
     * @note 促销下单
     * @datetime 2021-08-03 15:18
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|void
     */
    private function promoStore(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $jti = JWTAuth::getClaim('jti');
        $deliveryCoast = (string)$request->input('delivery_coast', '');
        $plaintext = opensslDecryptV2($deliveryCoast , $jti);
        $deliveryCoasts = \json_decode($plaintext , true);
        $goods = (array)$request->input('goods');
        $userName = $request->input('user_name' , '');
        $userContact = $request->input('user_contact' , '');
        $userAddress = $request->input('user_address' , '');
        $promoCode = $request->input('promo_code' , '');
        if(empty($promoCode))
        {
            abort(403 , 'The promo code does not exist!');
        }
        $gIds = array_keys($goods);
        if(empty($gIds))
        {
            abort(403 , 'Goods is empty!');
        }
        $key = "helloo:business:shopping_cart:service:account:".$userId;
        $cache = Redis::hmget($key , $gIds);
        $cache = array_map(function ($v){
            return (int)$v;
        } , $cache);
        $cache = array_combine($gIds , $cache);
        $cache = array_filter($cache , function ($v, $k){
            return !empty($v)&&!empty($k);
        } , ARRAY_FILTER_USE_BOTH);

        array_walk($goods, function($value, $key) use (&$goods ){
            $goods[$key] = (int)$value;
        });
        $filterGoods = array_filter($goods , function ($v, $k) use ($cache){
            return isset($cache[$k])&&$cache[$k]===$v;
        } , ARRAY_FILTER_USE_BOTH);
        if($goods!==$filterGoods)
        {
            abort(403 , 'An error occurred in the parameter!');
        }
        $gs = Goods::whereIn('id' , array_keys($goods))->get();
        $goodsStatus = $gs->every(function ($g , $k) {
            return $g->status===1;
        });
        if(!$goodsStatus)
        {
            abort(403 , 'The goods is out stock!');
        }
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
        $shopGoods = $gs;
        $userIds = $shopGoods->pluck('user_id')->unique()->toArray();
        if(count($userIds)!==1)
        {
            abort(403 , 'Promo code can only be used for one order!');
        }
        if($code->discount_type==='limit')
        {
            if(count($gIds)!==1)
            {
                abort(403 , 'Oops! This code is for double burger only!');
            }
            $promoGoods = DB::table('promo_goods')->where('code' , $promoCode)->first();
            if(empty($promoGoods))
            {
                abort(403 , 'Promo code does not exist!');
            }
            if($promoGoods->goods_id!==$gIds[0]||(int)$goods[$promoGoods->goods_id]!==1)
            {
                abort(403 , 'Oops! This code is for double burger only!!');
            }
        }
        $shopGoods->each(function($g) use ($goods){
            $g->goodsNumber = (int)$goods[$g->id];
        });
        $goodsIds = $shopGoods->pluck('id')->toArray();
        if(is_array($deliveryCoasts))
        {
            foreach ($deliveryCoasts as $k=>$v)
            {
                if(!isset($v['distance'], $v['delivery_cost'], $v['start'][0], $v['start'][1], $v['end'][0], $v['end'][1]) || !in_array((string)$k, $userIds, true))
                {
                    abort(422 , 'Illegal delivery cost format!');
                }
            }
        }
        $users = app(UserRepository::class)->findByUserIds($userIds);
        $phones = DB::table('users_phones')->whereIn('user_id' , $userIds)->get()->pluck('user_phone_country' , 'user_id')->toArray();
        $shopGoods = $shopGoods->groupBy('user_id')->toArray();
        $orderData = array();
        $returnData = array();
        $defaultDeliveryCost = config('common.default_delivery_cost');
        $now = date('Y-m-d H:i:s');
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
                    'user_longitude'=>$deliveryCoasts[$u]['start'][0],
                    'user_latitude'=>$deliveryCoasts[$u]['start'][1],
                    'shop_longitude'=>$deliveryCoasts[$u]['end'][0],
                    'shop_latitude'=>$deliveryCoasts[$u]['end'][1],
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
            $currency = isset($phones[$u])&&$phones[$u]==='251'?'BIRR':"USD";
            $data = array(
                'order_id'=>$orderId,
                'user_id'=> (string)$userId,
                'shop_id'=> (string)$u,
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
            if($code->free_delivery)
            {
                $deliveryCoast = 0;
            }else{
                $deliveryCoast = !is_array($deliveryCoasts)?$defaultDeliveryCost:((isset($deliveryCoasts[$u]['delivery_cost']))?round((float)($deliveryCoasts[$u]['delivery_cost']) , 2):$defaultDeliveryCost);
            }
            $data['delivery_coast'] = $deliveryCoast;
            $data['promo_code'] = $code->promo_code;
            $data['free_delivery'] = (int)$code->free_delivery;
            $data['reduction'] = $code->reduction;
            $data['discount'] = $code->percentage;
            $discount_type = (string)$code->discount_type;
            if($code->discount_type=='discount')
            {
                $totalPrice = round($promoPrice*$code->percentage/100 , 2);
                $discountedPrice = round($promoPrice*$code->percentage/100+$deliveryCoast+$packagingCost , 2);
            }else if($code->discount_type=='reduction'){
                $totalPrice = round($promoPrice-$code->reduction , 2);
                $discountedPrice = round($promoPrice-$code->reduction+$deliveryCoast+$packagingCost , 2);
            }else{
                $totalPrice = 0;
                $discountedPrice = round($deliveryCoast+$packagingCost , 2);
            }
            $data['discounted_price'] = $discountedPrice;
            $data['total_price'] = $totalPrice;
            $data['discount_type'] = $discount_type;
            array_push($orderData , $data);
            $user = $users->where('user_id' , $u)->first()->only('user_id' , 'user_name' , 'user_nick_name' , 'user_avatar_link' , 'user_contact' , 'user_address');
            $data['shop'] = new UserCollection($user);
            $data['detail'] = $shopGs;
            unset($data['discount_type']);
            $data['free_delivery'] = (bool)$data['free_delivery'];
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
                $codeResult = DB::table('promo_codes')->where('promo_code' , $promoCode)->decrement('limit');
                if($codeResult<=0)
                {
                    abort('500' , 'promo code update failed!');
                }
                if(!empty($orderAddresses))
                {
                    DB::table('orders_addresses')->insert($orderAddresses);
                }
                DB::commit();
            }catch (\Exception $e)
            {
                DB::rollBack();
                Log::error('promo_order_store_fail' , array(
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
            $this->dispatch((new Bitrix24Order($orderData , __FUNCTION__))->onQueue('helloo_{bitrix_order}'));
        }
        return OrderCollection::collection(collect($returnData));
    }

    /**
     * @note 特价下单
     * @datetime 2021-08-03 15:18
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|void
     */
    private function specialStore(Request $request)
    {
        $date = date('Y-m-d');
        $userContact = strtr((string)$request->input('user_contact', ''),array(" "=>"" , '+'=>""));
        $specialDateKey = "helloo:business:order:service:special:user".$date;
        if(empty($userContact)||Redis::SISMEMBER($specialDateKey , $userContact))
        {
            abort(422 , 'You have already enjoyed the discount today! Thank u!');
        }
        $user = auth()->user();
        $userId = $user->user_id;
        $jti = JWTAuth::getClaim('jti');
        $deliveryCoast = (string)$request->input('delivery_coast', '');
        $plaintext = opensslDecryptV2($deliveryCoast , $jti);
        $deliveryCoasts = \json_decode($plaintext , true);
        $goods = (array)$request->input('goods');
        $userName = $request->input('user_name' , '');
        $userAddress = $request->input('user_address' , '');
        $gIds = array_keys($goods);
        if(count($gIds)!==1)
        {
            abort(403 , 'Illegal request!');
        }
        $gs = Goods::whereIn('id' , $gIds)->get();
        $shopGoods = $gs->reject(function ($g) {
            return $g->status===0;
        });
        if($gs->count()!==1)
        {
            abort(403 , 'Only one goods can be ordered!');
        }else{
            $g = $gs->first();
            if((int)$goods[$g->id]!==1)
            {
                abort(403 , 'Only one goods can be ordered!!');
            }
            $key = "helloo:business:goods:service:special:".$g->id;
            $specialG = Redis::hgetall($key);
            if(empty($specialG))
            {
                abort(403 , 'Special goods does not exist!');
            }
        }
        $shopGoods->each(function($g) use ($goods){
            $key = "helloo:business:goods:service:special:".$g->id;
            $specialG = Redis::hgetall($key);
            $g->goodsNumber = (int)$goods[$g->id];
            if(!empty($specialG))
            {
                $g->specialPrice = round($specialG['special_price'] , 2);
                $g->packaging_cost = round($specialG['packaging_cost'] , 2);
                $g->free_delivery= (bool)$specialG['free_delivery'];
            }
        });
        $userIds = $shopGoods->pluck('user_id')->unique()->toArray();
        if(is_array($deliveryCoasts))
        {
            foreach ($deliveryCoasts as $k=>$v)
            {
                if(!isset($v['distance'], $v['delivery_cost'], $v['start'][0], $v['start'][1], $v['end'][0], $v['end'][1]) || !in_array((string)$k, $userIds, true))
                {
                    abort(422 , 'Illegal delivery cost format!');
                }
            }
        }
        $users = app(UserRepository::class)->findByUserIds($userIds);
        $phones = DB::table('users_phones')->whereIn('user_id' , $userIds)->get()->pluck('user_phone_country' , 'user_id')->toArray();
        $shopGoods = $shopGoods->groupBy('user_id')->toArray();
        $orderData = array();
        $returnData = array();
        $defaultDeliveryCost = config('common.default_delivery_cost');
        $now = date('Y-m-d H:i:s');
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
                    'user_longitude'=>$deliveryCoasts[$u]['start'][0],
                    'user_latitude'=>$deliveryCoasts[$u]['start'][1],
                    'shop_longitude'=>$deliveryCoasts[$u]['end'][0],
                    'shop_latitude'=>$deliveryCoasts[$u]['end'][1],
                    'created_at'=>$now,
                ));
            }
            $price = collect($shopGs)->sum(function ($shopG) use ($goods) {
                return $goods[$shopG['id']]*$shopG['price'];
            });
            $promoPrice = collect($shopGs)->sum(function ($shopG) use ($goods) {
                if(!isset($shopG['specialPrice']))
                {
                    return $goods[$shopG['id']]*$shopG['price'];
                }
                return $goods[$shopG['id']]*$shopG['specialPrice'];
            });
            $freeDelivery = collect($shopGs)->every(function ($shopG, $key) {
                return !empty($shopG['free_delivery']);
            });
            $packagingCost = collect($shopGs)->sum(function ($shopG) {
                return $shopG['goodsNumber']*$shopG['packaging_cost'];
            });
            $currency = isset($phones[$u])&&$phones[$u]==='251'?'BIRR':"USD";
            $data = array(
                'order_id'=>$orderId,
                'user_id'=> (string)$userId,
                'shop_id'=> (string)$u,
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
            $deliveryCoast = $freeDelivery?0:(!is_array($deliveryCoasts)?$defaultDeliveryCost:((isset($deliveryCoasts[$u]['delivery_cost']))?round(floatval($deliveryCoasts[$u]['delivery_cost']) , 2):$defaultDeliveryCost));
            $data['delivery_coast'] = $deliveryCoast;
            $data['promo_code'] = '';
            $data['free_delivery'] = (int)$freeDelivery;
            $data['reduction'] = 0;
            $data['discount'] = 100;
            $totalPrice = round($promoPrice , 2);
            $discountedPrice = round($promoPrice+$deliveryCoast+$packagingCost , 2);
            $data['discounted_price'] = $discountedPrice;
            $data['total_price'] = $totalPrice;
            $data['discount_type'] = '';
            array_push($orderData , $data);
            $user = $users->where('user_id' , $u)->first()->only('user_id' , 'user_name' , 'user_nick_name' , 'user_avatar_link' , 'user_contact' , 'user_address');
            $data['shop'] = new UserCollection($user);
            $data['detail'] = $shopGs;
            unset($data['discount_type']);
            $data['free_delivery'] = (bool)$data['free_delivery'];
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
                if(!empty($orderAddresses))
                {
                    DB::table('orders_addresses')->insert($orderAddresses);
                }
                DB::commit();
                Redis::sadd($specialDateKey , $userContact);
                Redis::expireat($specialDateKey , strtotime("+7 day"));
            }catch (\Exception $e)
            {
                DB::rollBack();
                Log::error('special_order_store_fail' , array(
                    'message'=>$e->getMessage(),
                    'user_id'=>$userId,
                    'data'=>$request->all()
                ));
                return $this->response->error('Order creation failed!' , 424);
            }
            OrderSynchronization::dispatch($returnData)->onQueue('helloo_{order_synchronization}');
            OrderSms::dispatch($orderData , 'batch')->onQueue('helloo_{delivery_order_sms}');
            $this->dispatch((new Bitrix24Order($orderData , __FUNCTION__))->onQueue('helloo_{bitrix_order}'));
        }
        return OrderCollection::collection(collect($returnData));
    }

    /**
     * @note 特价下单
     * @datetime 2021-07-30 20:07
     * @param Request $request
     * @return AnonymousCollection
     */
    public function specialOrder(Request $request)
    {
        $date = date('Y-m-d');
        $userContact = strtr((string)$request->input('user_contact', ''),array(" "=>"" , '+'=>""));
        $specialDateKey = "helloo:business:order:service:special:user".$date;
        if(empty($userContact)||Redis::SISMEMBER($specialDateKey , $userContact))
        {
            abort(422 , 'You have already enjoyed the discount today! Thank u!');
        }
        $goodsId = $request->input('goods_id');
        $userName = (string)$request->input('user_name', '');
        $userAddress = $request->input('user_address' , '');
        $jti = JWTAuth::getClaim('jti');
        $deliveryCoast = (string)$request->input('delivery_coast', '');
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
        if(empty($specialGoods)||$specialGoods->status===0)
        {
            Redis::del($key);
            abort(403 , 'This goods is not a special offer!');
        }
        $price = round($specialG['special_price'] , 2);
        $user = auth()->user();
        $userId = $user->user_id;
        $orderId = app('snowflake')->id();
        $goods = Goods::where('id' , $goodsId)->firstOrFail();
        $orderAddresses = array();
        $now = date("Y-m-d H:i:s");
        if(is_array($deliveryCoasts))
        {
            foreach ($deliveryCoasts as $k=>$v)
            {
                if(!isset($v['distance'], $v['delivery_cost'], $v['start'][0], $v['start'][1], $v['end'][0], $v['end'][1]) || $k !== $goods->user_id)
                {
                    abort(422 , 'Illegal delivery cost format!');
                }
                array_push($orderAddresses , array(
                    'id'=>app('snowflake')->id(),
                    'user_id'=>$userId,
                    'order_id'=>$orderId,
                    'user_longitude'=>$v['start'][0],
                    'user_latitude'=>$v['start'][1],
                    'shop_longitude'=>$v['end'][0],
                    'shop_latitude'=>$v['end'][1],
                    'distance'=>$v['distance'],
                    'created_at'=>$now,
                ));
            }
        }
        $orderPrice = $goods->price;
        $goods->specialPrice = $price;
        $goods->goodsNumber = 1;
        $data = array(
            'order_id'=>$orderId,
            'user_id'=> (string)$userId,
            'shop_id'=> (string)$goods->user_id,
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
        $defaultDeliveryCost = config('common.default_delivery_cost');
        $deliveryCoast = !is_array($deliveryCoasts)?$defaultDeliveryCost:((isset($deliveryCoasts[$goods->user_id]['delivery_cost']))?round(floatval($deliveryCoasts[$goods->user_id]['delivery_cost']) , 2):$defaultDeliveryCost);
        $data['delivery_coast'] = !empty((int)$specialG['free_delivery'])?0:$deliveryCoast;
        $data['promo_code'] = '';
        $data['free_delivery'] = $specialG['free_delivery'];
        $data['reduction'] = 0;
        $data['discount'] = 100;
        $data['discounted_price'] = round($price+$data['delivery_coast']+$data['packaging_cost'] , 2);
        $data['total_price'] = $price;
        $returnData = $data;
        $returnData['free_delivery'] = (bool)$specialG['free_delivery'];
        $returnData['detail'] = $goods->toArray();
        $returnData['shop'] = new UserCollection($user);
        DB::table('orders')->insert($data);
        if(!empty($orderAddresses))
        {
            DB::table('orders_addresses')->insert($orderAddresses);
        }
        Redis::sadd($specialDateKey , $userContact);
        Redis::expireat($specialDateKey , strtotime("+7 day"));
        unset($returnData['discount_type']);
        $data['free_delivery'] = (bool)$data['free_delivery'];
        OrderSynchronization::dispatch($returnData , 'special')->onQueue('helloo_{order_synchronization}');
        OrderSms::dispatch(array($data) , 'batch')->onQueue('helloo_{delivery_order_sms}');
        return new OrderCollection(collect($returnData));
    }

    /**
     * @note 订单预览
     * @datetime 2021-07-12 17:57
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function preview(Request $request)
    {
        $type = $request->input('type' , '');
        $promoCode = $request->input('promo_code' , '');
        if($type=='special')
        {
            return $this->specialPreview($request);
        }
        if(!empty($promoCode))
        {
            return $this->promoPreview($request);
        }
        return $this->normalPreview($request);
    }

    /**
     * @note 正常预览订单
     * @datetime 2021-08-03 13:36
     * @param Request $request
     * @return mixed
     */
    private function normalPreview(Request $request)
    {
        $user = auth()->user();
        $jti = JWTAuth::getClaim('jti');
        $userId = $user->user_id;
        $goods = (array)$request->input('goods');
        $deliveryCoast = (string)$request->input('delivery_coast', '');
        $plaintext = opensslDecryptV2($deliveryCoast , $jti);
        $deliveryCoasts = \json_decode($plaintext , true);
        $key = "helloo:business:shopping_cart:service:account:".$userId;
        $gIds = array_keys($goods);
        if(empty($gIds))
        {
            abort(403 , 'Illegal request!');
        }
        $cache = Redis::hmget($key , $gIds);
        $cache = array_combine($gIds , $cache);
        $filterGoods = array_filter($cache , function ($v, $k){
            return !empty($v)&&!empty($k);
        } , ARRAY_FILTER_USE_BOTH);
        if(empty($filterGoods))
        {
            abort(403 , 'There is no goods in the shopping cart!');
        }
        $gs = Goods::whereIn('id' , array_keys($filterGoods))->get();
        $goodsStatus = $gs->every(function ($g , $k) {
            return $g->status===1;
        });
        if(!$goodsStatus)
        {
            abort(403 , 'Goods is out stock!');
        }
        $shopGoods = $gs;
        $userIds = $shopGoods->pluck('user_id')->unique()->toArray();
        $orderNumber = count($userIds);
        if($orderNumber>3||$orderNumber<=0)
        {
            abort(403 , 'Only 1 to 3 orders can be placed at a time!');
        }
        if(is_array($deliveryCoasts))
        {
            foreach ($deliveryCoasts as $k=>$v)
            {
                if(!isset($v['distance'], $v['delivery_cost'], $v['start'][0], $v['start'][1], $v['end'][0], $v['end'][1]) || !in_array((string)$k, $userIds, true))
                {
                    abort(422 , 'Illegal delivery cost format!');
                }
            }
        }
//        $discounted = boolval(Redis::get("helloo:business:order:service:discounted:switch"));
        $status = 0;
        $message = '';
        $shopGoods->each(function($g) use ($filterGoods){
            $g->goodsNumber = (int)$filterGoods[$g->id];
        });
        $phones = DB::table('users_phones')->whereIn('user_id' , $userIds)->get()->pluck('user_phone_country' , 'user_id')->toArray();
        $shopGoods = collect($shopGoods->groupBy('user_id')->toArray());
        $shops = app(UserRepository::class)->findByUserIds($userIds)->toArray();
        $returnData = array();
        $defaultDeliveryCost = config('common.default_delivery_cost');
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
            $currency = isset($phones[$shop['user_id']])&&$phones[$shop['user_id']]==='251'?'BIRR':"USD";
            $data['currency'] = $currency;
            $deliveryCoast = !is_array($deliveryCoasts)?$defaultDeliveryCost:((isset($deliveryCoasts[$shop['user_id']]['delivery_cost']))?round((float)($deliveryCoasts[$shop['user_id']]['delivery_cost']) , 2):$defaultDeliveryCost);
            $totalPrice = round($promoPrice , 2);
//            if($discounted&&$orderNumber==1)
//            {
//                $discount = round(floatval(Redis::get('helloo:business:order:service:first:discount')) , 2);
//                if($discount>0)
//                {
//                    $firstKey = "helloo:business:order:service:first";
//                    $r = Redis::sismember($firstKey , $user->user_id);
//                    if($r)
//                    {
//                        $firstTotal = $totalPrice-$discount;
//                        $totalPrice = $firstTotal<0?0:$totalPrice;
//                    }
//                }
//            }
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
     * @note 促销预览订单
     * @datetime 2021-08-03 15:19
     * @param Request $request
     * @return mixed
     */
    private function promoPreview(Request $request)
    {
        $user = auth()->user();
        $jti = JWTAuth::getClaim('jti');
        $userId = $user->user_id;
        $goods = (array)$request->input('goods');
        $deliveryCoast = (string)$request->input('delivery_coast', '');
        $plaintext = opensslDecryptV2($deliveryCoast , $jti);
        $deliveryCoasts = \json_decode($plaintext , true);
        $promoCode = (string)$request->input('promo_code', '');
        $key = "helloo:business:shopping_cart:service:account:".$userId;
        $gIds = array_keys($goods);
        if(empty($gIds))
        {
            abort(403 , 'Illegal request!');
        }
        $cache = Redis::hmget($key , $gIds);
        $cache = array_combine($gIds , $cache);
        $filterGoods = array_filter($cache , function ($v, $k){
            return !empty($v)&&!empty($k);
        } , ARRAY_FILTER_USE_BOTH);
        if(empty($filterGoods))
        {
            abort(403 , 'There is no goods in the shopping cart!');
        }
        $gs = Goods::whereIn('id' , array_keys($filterGoods))->get();
        $goodsStatus = $gs->every(function ($g , $k) {
            return $g->status===1;
        });
        if(!$goodsStatus)
        {
            abort(403 , 'Goods is out stock!');
        }
        $shopGoods = $gs;
        $userIds = $shopGoods->pluck('user_id')->unique()->toArray();
        if(count($userIds)!==1)
        {
            abort(403 , 'Promo code can only be used for one order!');
        }
        if(is_array($deliveryCoasts))
        {
            foreach ($deliveryCoasts as $k=>$v)
            {
                if(!isset($v['distance'], $v['delivery_cost'], $v['start'][0], $v['start'][1], $v['end'][0], $v['end'][1]) || !in_array((string)$k, $userIds, true))
                {
                    abort(422 , 'Illegal delivery cost format!');
                }
            }
        }
        $discounted = (bool)Redis::get("helloo:business:order:service:discounted:switch");
        $flag = $status = 0;
        $message = '';
        $code = PromoCode::where('promo_code' , $promoCode)->first();
        if(empty($code)||$code->limit<=0||(!empty($code->deadline)&&$code->deadline<date('Y-m-d')))
        {
            $flag = 1;
            $message = "Sorry this code is invalid!";
        }
        if($flag===0&&$code->discount_type==='limit')
        {
            if(count($gIds)!==1)
            {
                abort(403 , 'Oops! This code is for double burger only!');
            }
            $promoGoods = DB::table('promo_goods')->where('code' , $promoCode)->first();
            if(empty($promoGoods))
            {
                abort(403 , 'Promo code does not exist!');
            }
            if($promoGoods->goods_id!==$gIds[0]||(int)$goods[$promoGoods->goods_id]!==1)
            {
                abort(403 , 'Oops! This code is for double burger only!!');
            }
        }
        if(!$discounted&&!empty($code))
        {
            $discountedGoods = $gs->every(function ($g) {
                return $g->discounted_price<0;
            });
            if(!$discountedGoods)
            {
                $flag = 2;
                $message = "The conditions for using promotional codes are not met!";
            }
        }
        $shopGoods->each(function($g) use ($filterGoods){
            $g->goodsNumber = (int)$filterGoods[$g->id];
        });
        $phones = DB::table('users_phones')->whereIn('user_id' , $userIds)->get()->pluck('user_phone_country' , 'user_id')->toArray();
        $shopGoods = collect($shopGoods->groupBy('user_id')->toArray());
        $shops = app(UserRepository::class)->findByUserIds($userIds)->toArray();
        $returnData = array();
        $defaultDeliveryCost = config('common.default_delivery_cost');
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
            $currency = isset($phones[$shop['user_id']])&&$phones[$shop['user_id']]==='251'?'BIRR':"USD";
            $data['currency'] = $currency;
            if(!empty($code)&&$flag===0)
            {
                $status = 200;
                $message = $code->description;
                $deliveryCoast = $code->free_delivery?0:(!is_array($deliveryCoasts)?$defaultDeliveryCost:((isset($deliveryCoasts[$shop['user_id']]['delivery_cost']))?round((float)($deliveryCoasts[$shop['user_id']]['delivery_cost']) , 2):$defaultDeliveryCost));
                $data['deliveryCoast'] = $deliveryCoast;
                if($code->discount_type=='discount')
                {
                    $totalPrice = round($promoPrice*$code->percentage/100 , 2);
                }else if($code->discount_type=='reduction'){
                    $totalPrice = round($promoPrice-$code->reduction , 2);
                }else{
                    $totalPrice = 0;
                }
            }else{
                $deliveryCoast = !is_array($deliveryCoasts)?$defaultDeliveryCost:((isset($deliveryCoasts[$shop['user_id']]['delivery_cost']))?round((float)($deliveryCoasts[$shop['user_id']]['delivery_cost']) , 2):$defaultDeliveryCost);
                $totalPrice = round($promoPrice , 2);
            }
//            if($discounted)
//            {
//                $discount = round(floatval(Redis::get('helloo:business:order:service:first:discount')) , 2);
//                if($discount>0)
//                {
//                    $firstKey = "helloo:business:order:service:first";
//                    $r = Redis::sismember($firstKey , $user->user_id);
//                    if($r)
//                    {
//                        $firstTotal = $totalPrice-$discount;
//                        $totalPrice = $firstTotal<0?0:$totalPrice;
//                    }
//                }
//            }
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
     * @note 特价预览订单
     * @datetime 2021-08-03 13:36
     * @param Request $request
     * @return mixed
     */
    private function specialPreview(Request $request)
    {
        $jti = JWTAuth::getClaim('jti');
        $filterGoods = (array)$request->input('goods');
        $deliveryCoast = (string)($request->input('delivery_coast' , ''));
        $plaintext = opensslDecryptV2($deliveryCoast , $jti);
        $deliveryCoasts = \json_decode($plaintext , true);
        $gIds = array_keys($filterGoods);
        if(count($gIds)!==1)
        {
            abort(403 , 'Illegal request!');
        }
        $gs = Goods::whereIn('id' , $gIds)->get();
        $shopGoods = $gs->reject(function ($g) {
            return $g->status===0;
        });
        if($gs->count()!==1)
        {
            abort(403 , 'Only one goods can be ordered!');
        }else{
            $g = $gs->first();
            if((int)$filterGoods[$g->id]!==1)
            {
                abort(403 , 'Only one goods can be ordered!!');
            }
            $key = "helloo:business:goods:service:special:".$g->id;
            $specialG = Redis::hgetall($key);
            if(empty($specialG))
            {
                abort(403 , 'Special goods does not exist!');
            }
        }
        $userIds = $shopGoods->pluck('user_id')->unique()->toArray();
        if(is_array($deliveryCoasts))
        {
            foreach ($deliveryCoasts as $k=>$v)
            {
                if(!isset($v['distance'], $v['delivery_cost'], $v['start'][0], $v['start'][1], $v['end'][0], $v['end'][1]) || !in_array((string)$k, $userIds, true))
                {
                    abort(422 , 'Illegal delivery cost format!');
                }
            }
        }
        $status = 200;
        $message = '';
        $shopGoods->each(function($g) use ($filterGoods){
            $key = "helloo:business:goods:service:special:".$g->id;
            $specialG = Redis::hgetall($key);
            $g->goodsNumber = (int)$filterGoods[$g->id];
            if(!empty($specialG))
            {
                $g->specialPrice = round($specialG['special_price'] , 2);
                $g->packaging_cost = round($specialG['packaging_cost'] , 2);
                $g->free_delivery= (bool)$specialG['free_delivery'];
            }
        });
        $phones = DB::table('users_phones')->whereIn('user_id' , $userIds)->get()->pluck('user_phone_country' , 'user_id')->toArray();
        $shopGoods = collect($shopGoods->groupBy('user_id')->toArray());
        $shops = app(UserRepository::class)->findByUserIds($userIds)->toArray();
        $returnData = array();
        $defaultDeliveryCost = config('common.default_delivery_cost');
        foreach ($shops as $shop)
        {
            $shopGs = $shopGoods->get($shop['user_id']);
            $price = collect($shopGs)->sum(function ($shopG) {
                return $shopG['goodsNumber']*$shopG['price'];
            });
            $promoPrice = collect($shopGs)->sum(function ($shopG) {
                if(isset($shopG['specialPrice']))
                {
                    return $shopG['goodsNumber']*$shopG['specialPrice'];
                }
                return $shopG['goodsNumber']*$shopG['price'];
            });
            $packagingCost = collect($shopGs)->sum(function ($shopG) {
                return $shopG['goodsNumber']*$shopG['packaging_cost'];
            });
            $currency = isset($phones[$shop['user_id']])&&$phones[$shop['user_id']]==='251'?'BIRR':"USD";
            $data['currency'] = $currency;
            $freeDelivery = collect($shopGs)->every(function ($value, $key) {
                return !empty($value['free_delivery']);
            });
            $deliveryCoast = $freeDelivery?0:(!is_array($deliveryCoasts)?$defaultDeliveryCost:((isset($deliveryCoasts[$shop['user_id']]['delivery_cost']))?round(floatval($deliveryCoasts[$shop['user_id']]['delivery_cost']) , 2):$defaultDeliveryCost));
            $totalPrice = round($promoPrice , 2);
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
