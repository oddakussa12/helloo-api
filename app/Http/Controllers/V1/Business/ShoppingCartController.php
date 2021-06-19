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
use App\Http\Requests\StoreDeliveryOrderRequest;

class ShoppingCartController extends BaseController
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $carts = DB::table('shopping_carts')->where('user_id' , $userId)->get();
        $goods = $carts->pluck('number' , 'goods_id')->toArray();
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
    public function store(Request $request)
    {
        $type = $request->input('type' , 'store');
        $user = auth()->user();
        $userId = $user->user_id;
        $key = "helloo:business:shopping_cart:service:account:".$userId;
        $goodsId = $request->input('goods_id' , '');
        $goods = Goods::where('id' , $goodsId)->firstOrFail();
        if($goods->status==0)
        {
            abort(404 , 'This goods does not exist or out of stock!');
        }
        if($type=='store')
        {
            $number = Redis::hincrby($key , $goodsId , 1);
        }else{
            $number = intval($request->input('number' , 1));
            if($number<0||$number>=100)
            {
                abort(422 , 'The number of goods is the most right to add 100!');
            }
            if($number==0)
            {
                Redis::hdel($key , $goodsId);
            }else{
                Redis::hset($key , $goodsId , $number);
            }
        }
        ShoppingCart::dispatch($goods , $user , $number)->onQueue('helloo_{business_shopping_cart}');
        return $this->response->created(null , array(
            'data'=>array(
                'goods' => new AnonymousCollection($goods),
                'number' =>$number,
            )
        ));
    }

    public function destroy(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $key = "helloo:business:shopping_cart:service:account:".$userId;
        $goodsId = $request->input('goods_id' , '');
        $goods = Goods::where('id' , $goodsId)->firstOrFail();
        if($goods->status==0)
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
