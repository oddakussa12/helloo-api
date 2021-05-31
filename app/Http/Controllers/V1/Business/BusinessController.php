<?php

namespace App\Http\Controllers\V1\Business;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Business\Goods;
use App\Jobs\BusinessSearchLog;
use Illuminate\Support\Facades\DB;
use App\Resources\AnonymousCollection;
use App\Http\Controllers\V1\BaseController;

class BusinessController extends BaseController
{
    public function search(Request $request)
    {
        $userId = auth()->id();
        $keyword = escape_like(strval($request->input('keyword' , '')));
        if(!empty($keyword))
        {
            $users = User::where('user_shop' , 1)->where('user_verified' , 1)->where(function ($query) use ($keyword) {
                $query->where('user_nick_name', 'like', "%{$keyword}%")->orWhere('user_name', 'like', "%{$keyword}%");
            })->limit(10)->get();
            $goods = Goods::where('name', 'like', "%{$keyword}%")->limit(10)->get();
            $goodsIds = $goods->pluck('id')->toArray();
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
            $goods = $users = collect();
        }
        BusinessSearchLog::dispatch($userId , $keyword)->onQueue('helloo_{business_search_logs}');
        return $this->response->array(array(
            'data'=>array(
                'user'=>AnonymousCollection::collection($users),
                'goods'=>AnonymousCollection::collection($goods)
            )
        ));
    }
}
