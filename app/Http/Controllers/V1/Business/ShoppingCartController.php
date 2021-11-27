<?php

namespace App\Http\Controllers\V1\Business;

use App\Jobs\ShoppingCart;
use Illuminate\Http\Request;
use App\Models\Business\Goods;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Resources\AnonymousCollection;
use App\Http\Controllers\V1\BaseController;
use App\Repositories\Contracts\UserRepository;

class ShoppingCartController extends BaseController
{
    /**
     * @note 我的购物车
     * @datetime 2021-07-12 17:58
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $userId = intval($request->input('user_id' , 0));
        $key = "helloo:business:shopping_cart:service:account:".$user->user_id;
        $cache = Redis::hgetall($key);
        $goods = array_filter($cache , function ($v, $k){
            return !empty($v)&&!empty($k);
        } , ARRAY_FILTER_USE_BOTH);
        if($userId>0)
        {
            $gs = Goods::where('user_id' , $userId)->whereIn('id' , array_keys($goods))->get();
        }else{
            $gs = Goods::whereIn('id' , array_keys($goods))->get();
        }
        $shopGoods = $gs->reject(function ($g) {
            return $g->status==0;
        });
        $shopGoods->each(function($g) use ($goods){
            $g->goodsNumber = intval($goods[$g->id]);
        });
        $userIds = $shopGoods->pluck('user_id')->unique()->toArray();
        $phones = DB::table('users_phones')->whereIn('user_id' , $userIds)->get()->pluck('user_phone_country' , 'user_id')->toArray();
        $shopGoods = collect($shopGoods->groupBy('user_id')->toArray());
        $shops = app(UserRepository::class)->findByUserIds($userIds)->toArray();
        $shoppingCarts = array();
        $defaultDeliveryCost = config('common.default_delivery_cost');
        foreach ($shops as $k=>$shop)
        {
            $shop = collect($shop)->only('user_id' , 'user_name' , 'user_nick_name' , 'user_avatar_link')->toArray();
            $currency = isset($phones[$shop['user_id']])&&$phones[$shop['user_id']]=='251'?'BIRR':"USD";
            $shopGs = collect($shopGoods->get($shop['user_id']));
            $price = $shopGs->sum(function($shopG){
                return $shopG['goodsNumber']*$shopG['price'];
            });
            $shop['goods'] = AnonymousCollection::collection($shopGs);
            $shop['user_currency'] = $currency;
            $shop['deliveryCoast'] = $defaultDeliveryCost;
            $shop['subTotal'] = $price;
            $shoppingCarts[$k] = new UserCollection($shop);
        }
        return AnonymousCollection::collection(collect($shoppingCarts)->values());
    }


    /**
     * @note 购物车新增
     * @datetime 2021-07-12 17:58
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function store(Request $request)
    {
        $type = $request->input('type' , 'store');
        $shopId = (int)$request->input('user_id', 0);
        $user = auth()->user();
        $userId = $user->user_id;
        $key = "helloo:business:shopping_cart:service:account:".$userId;
        $goodsId = (string)$request->input('goods_id' , '');
        $goods = Goods::where('id' , $goodsId)->firstOrFail();
        if($goods->status===0)
        {
            abort(404 , 'This goods does not exist or out of stock!');
        }
        if($type=='store')
        {
            if(!Redis::hexists($key , $goodsId))
            {
                if(Redis::hlen($key)>=20)
                {
                    abort(422 , trans('shopping_cart.shopping_max'));
                }
            }else{
                $number = Redis::hget($key , $goodsId);
                if($number>=50)
                {
                    abort(422 , 'The number of goods is the most right to add 50!');
                }
            }
            $number = Redis::hincrby($key , $goodsId , 1);
        }else{
            $number = (int)$request->input('number', 1);
            if($number<0||$number>50)
            {
                abort(422 , 'The number of goods is the most right to add 50!');
            }
            if($number ===0)
            {
                Redis::hdel($key , $goodsId);
            }else{
                if(!Redis::hexists($key , $goodsId))
                {
                    if(Redis::hlen($key)>=20)
                    {
                        abort(422 , trans('shopping_cart.shopping_max'));
                    }
                }
                Redis::hset($key , $goodsId , $number);
            }
        }
        ShoppingCart::dispatch($goods , $user , $number)->onQueue('helloo_{business_shopping_cart}');
        $cache = Redis::hgetall($key);
        $goods = array_filter($cache , function ($v, $k){
            return !empty($v)&&!empty($k);
        } , ARRAY_FILTER_USE_BOTH);
        $gs = Goods::whereIn('id' , array_keys($goods))->get();
        if($shopId>0)
        {
            $gs = $gs->reject(function ($g) use ($shopId) {
                return $g->user_id!==(string)$shopId;
            });
        }
        $gs->each(function($g) use ($goods){
            $g->goodsNumber = (int)$goods[$g->id];
            $g->ddd = 0;
            $g->ddd = 5;
        });
        $userIds = $gs->pluck('user_id')->unique()->toArray();
        $phones = DB::table('users_phones')->whereIn('user_id' , $userIds)->get()->pluck('user_phone_country' , 'user_id')->toArray();
        $shopGoods = collect($gs->groupBy('user_id')->toArray());
        $shops = app(UserRepository::class)->findByUserIds($userIds)->toArray();
        $defaultDeliveryCost = config('common.default_delivery_cost');
        foreach ($shops as $k=>$shop)
        {
            $shop = collect($shop)->only('user_id' , 'user_name' , 'user_nick_name' , 'user_avatar_link')->toArray();
            $currency = isset($phones[$shop['user_id']])&&$phones[$shop['user_id']]==='251'?'BIRR':"USD";
            $shopGs = collect($shopGoods->get($shop['user_id']));
            $price = $shopGs->sum(function($shopG){
                return $shopG['goodsNumber']*$shopG['price'];
            });
            $discountedPrice = collect($shopGs)->sum(function ($shopG) {
                if($shopG['discounted_price']<0)
                {
                    return $shopG['goodsNumber']*$shopG['price'];
                }
                return $shopG['goodsNumber']*$shopG['discounted_price'];
            });
            $shop['goods'] = AnonymousCollection::collection($shopGs);
            $shop['user_currency'] = $currency;
            $shop['deliveryCoast'] = $defaultDeliveryCost;
            $shop['subTotal'] = $price;
            $shop['subDiscountedTotal'] = $discountedPrice;
            $shops[$k] = new UserCollection($shop);
        }
        return AnonymousCollection::collection(collect($shops)->values());
    }

    /**
     * @note 购物车删除
     * @datetime 2021-07-12 17:58
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function destroy(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $key = "helloo:business:shopping_cart:service:account:".$userId;
        $goodsId = $request->input('goods_id' , '');
        $goods = Goods::where('id' , $goodsId)->firstOrFail();
        if($goods->status===0)
        {
            abort(404 , 'This goods does not exist or out of stock!');
        }
        $result = Redis::hdel($key , $goodsId);
        if($result<=0)
        {
            abort(404 , 'This goods is not in the shopping cart!');
        }
        ShoppingCart::dispatch($goods , $user , 0)->onQueue('helloo_{business_shopping_cart}');
        return $this->response->noContent();
    }
}
