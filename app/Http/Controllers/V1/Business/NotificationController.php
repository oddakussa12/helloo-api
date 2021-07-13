<?php

namespace App\Http\Controllers\V1\Business;

use Illuminate\Http\Request;
use App\Models\Business\Goods;
use App\Resources\AnonymousCollection;
use App\Http\Controllers\V1\BaseController;

class NotificationController extends BaseController
{
    /**
     * @note 通知
     * @datetime 2021-07-12 17:56
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function activities(Request $request)
    {
        $user_id = strval($request->input('user_id' , ''));
        if(!empty($user_id))
        {
            $appends['user_id'] = $user_id;
            $goods = Goods::where('user_id', $user_id)->where('like' , '>' , 0)->select('id' , 'name' , 'like' , 'image' , 'liked_at' , 'status' , 'price' , 'currency')
                ->orderByDesc('liked_at')
                ->paginate(10);
            $goods = $goods->appends($appends);
        }else{
            $goods = collect();
        }
        if($goods->isNotEmpty())
        {
            $goods->makeVisible('liked_at')->makeVisible('status')->each(function($g){
                $g->format_liked_at = dateTrans($g->liked_at);
            });
        }
        return AnonymousCollection::collection($goods);
    }
}
