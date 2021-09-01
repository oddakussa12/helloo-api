<?php

namespace App\Traits;

use App\Jobs\OrderSms;
use App\Jobs\Bitrix24Order;
use Illuminate\Http\Request;
use App\Models\Business\Goods;
use App\Resources\UserCollection;
use App\Models\Business\PromoCode;
use App\Jobs\OrderSynchronization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Resources\AnonymousCollection;
use App\Repositories\Contracts\UserRepository;

trait BotOrder
{
    /**
     * @note Bot 下单
     * @datetime 2021-08-14 14:12
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|void
     */
    public function botStore(Request $request)
    {
        $type = $request->input('type' , '');
        if($type=='special')
        {
            return $this->specialBotStore($request);
        }
        $promoCode = $request->input('promo_code' , '');
        if(!empty($promoCode))
        {
            return $this->promoBotStore($request);
        }
        return $this->normalBotStore($request);
    }

    /**
     * @note Bot 正常下单
     * @datetime 2021-08-14 14:12
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|void
     */
    private function normalBotStore(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $jti = str_pad((string)$userId,16,"0",STR_PAD_RIGHT);
        $deliveryCoast = (string)$request->input('delivery_coast', '');
        $plaintext = opensslDecryptV2($deliveryCoast , $jti);
        $deliveryCoasts = \json_decode($plaintext , true);
        $goods = (array)$request->input('goods');
        $userName = $request->input('user_name' , '');
        $userContact = $request->input('user_contact' , '');
        $userAddress = $request->input('user_address' , '');
        $filterGoods = array_filter($goods , function ($v, $k){
            $v = (int)$v;
            return !empty($v)&&!empty($k)&&$v>0&&$v<=50;
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
        $defaultDeliveryCost = config('common.default_delivery_cost');
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
                'first_order'=>0,
                'currency'=>$currency,
                'created_at'=>$now,
                'updated_at'=>$now,
            );
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
                Log::info('normal_order_store_fail' , array(
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
        return AnonymousCollection::collection(collect($returnData));
    }

    /**
     * @note Bot 促销下单
     * @datetime 2021-08-14 14:12
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|void
     */
    private function promoBotStore(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $jti = str_pad((string)$userId,16,"0",STR_PAD_RIGHT);
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
        $filterGoods = array_filter($goods , function ($v, $k){
            $v = (int)$v;
            return !empty($v)&&!empty($k)&&$v>0&&$v<=50;
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
        $shopGoods->each(function($g) use ($goods){
            $g->goodsNumber = (int)$goods[$g->id];
        });
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
        $defaultDeliveryCost = config('common.default_delivery_cost');
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
                'first_order'=>0,
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
            }else{
                $totalPrice = round($promoPrice-$code->reduction , 2);
                $discountedPrice = round($promoPrice-$code->reduction+$deliveryCoast+$packagingCost , 2);
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
                Log::info('promo_order_store_fail' , array(
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
        return AnonymousCollection::collection(collect($returnData));
    }

    /**
     * @note Bot 特价下单
     * @datetime 2021-08-14 14:12
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|void
     */
    private function specialBotStore(Request $request)
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
        $jti = str_pad((string)$userId,16,"0",STR_PAD_RIGHT);
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
            if($goods[$g->id]!==1)
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
        $now = date('Y-m-d H:i:s');
        $orderAddresses = array();
        $defaultDeliveryCost = config('common.default_delivery_cost');
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
                'first_order'=>0,
                'currency'=>$currency,
                'created_at'=>$now,
                'updated_at'=>$now,
            );
            $deliveryCoast = $freeDelivery?0:(!is_array($deliveryCoasts)?$defaultDeliveryCost:((isset($deliveryCoasts[$u]['delivery_cost']))?round((float)($deliveryCoasts[$u]['delivery_cost']) , 2):$defaultDeliveryCost));
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
                Log::info('special_order_store_fail' , array(
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
        return AnonymousCollection::collection(collect($returnData));
    }
}
