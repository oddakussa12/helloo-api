<?php

namespace App\Http\Controllers\V1\Business;

use App\Jobs\ShoppingCart;
use Illuminate\Http\Request;
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
            'goods' => new AnonymousCollection($goods),
            'number' =>$number,
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
