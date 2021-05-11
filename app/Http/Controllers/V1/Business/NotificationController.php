<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\V1\BaseController;

class NotificationController extends BaseController
{
    public function activities(Request $request)
    {
        $shopId = strval($request->input('shop_id' , ''));
        if(!empty($shopId))
        {
            $goods = Goods::where('shop_id', $shopId)->where('like' , '>' , 0)
                ->orderByDesc('created_at')
                ->paginate(10);
        }else{
            $goods = collect();
        }
        $goods->each(function($g){
            $g->format_liked_at = dateTrans($g->liked_at);
        });
        return AnonymousCollection::collection($goods);
    }
}
