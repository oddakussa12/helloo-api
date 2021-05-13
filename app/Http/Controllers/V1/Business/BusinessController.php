<?php

namespace App\Http\Controllers\V1\Business;

use Illuminate\Http\Request;
use App\Models\Business\Shop;
use App\Models\Business\Goods;
use App\Jobs\BusinessSearchLog;
use App\Resources\AnonymousCollection;
use App\Http\Controllers\V1\BaseController;
use Illuminate\Support\Facades\DB;

class BusinessController extends BaseController
{
    public function search(Request $request)
    {
        $userId = auth()->id();
        $keyword = escape_like(strval($request->input('keyword' , '')));
        if(!empty($keyword))
        {
            $shops = Shop::where('nick_name', 'like', "%{$keyword}%")->limit(10)->get();
            $goods = Goods::where('name', 'like', "%{$keyword}%")->limit(10)->get();
            $goodsIds = $goods->pluck('goods_id')->toArray();
            if(!empty($goodsIds))
            {
                $likes = collect(DB::table('likes_goods')->where('user_id' , $userId)->whereIn('goods_id' , $goodsIds)->get()->map(function ($value){
                    return (array)$value;
                }))->pluck('goods_id')->unique()->toArray();
                $goods->each(function($g) use ($likes){
                    $g->likeState = in_array($g->id , $likes);
                });
            }
        }else{
            $goods = $shops = collect();
        }
        BusinessSearchLog::dispatch($userId , $keyword)->onQueue('helloo_{business_search_log}');
        return $this->response->array(array(
            'data'=>array(
                'shop'=>AnonymousCollection::collection($shops),
                'goods'=>AnonymousCollection::collection($goods)
            )
        ));
    }
}
