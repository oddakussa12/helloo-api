<?php

namespace App\Http\Controllers\V1\Business;

use Illuminate\Http\Request;
use App\Models\Business\Goods;
use App\Resources\AnonymousCollection;
use App\Http\Controllers\V1\BaseController;

class NotificationController extends BaseController
{
    public function activities(Request $request)
    {
        $shopId = strval($request->input('shop_id' , ''));
        if(!empty($shopId))
        {
            $appends['shop_id'] = $shopId;
            $goods = Goods::where('shop_id', $shopId)->where('like' , '>' , 0)->select('id' , 'name' , 'like' , 'image' , 'liked_at' , 'status' , 'price' , 'currency')
                ->orderByDesc('created_at')
                ->paginate(10);
            $goods = $goods->appends($appends);
        }else{
            $goods = collect();
        }
        $goods->makeVisible('liked_at')->makeVisible('status')->each(function($g){
            $g->format_liked_at = dateTrans($g->liked_at);
        });
        return AnonymousCollection::collection($goods);
    }
}