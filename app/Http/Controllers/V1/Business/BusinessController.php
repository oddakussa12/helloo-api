<?php

namespace App\Http\Controllers\V1\Business;

use Illuminate\Http\Request;
use App\Models\Business\Shop;
use App\Models\Business\Goods;
use App\Jobs\BusinessSearchLog;
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
            $shops = Shop::where('nick_name', 'like', "%{$keyword}%")->limit(10)->get();
            $goods = Goods::where('name', 'like', "%{$keyword}%")->limit(10)->get();
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
