<?php

namespace App\Http\Controllers\V1\Business;


use Illuminate\Http\Request;
use App\Models\Business\ShopTag;
use App\Resources\AnonymousCollection;
use App\Http\Controllers\V1\BaseController;
use App\Models\Business\ShopTagTranslation;
use Illuminate\Support\Facades\Redis;

class ShopTagController extends BaseController
{
    public function index(Request $request)
    {
        $key = 'helloo:business:service:tags';
        if(Redis::exists($key))
        {
            $data = \json_decode(Redis::get($key , true));
        }else{
            $goodsTags = ShopTag::all();
            $tagIds = $goodsTags->pluck('id')->toArray();
            $goodsTagsTranslations = ShopTagTranslation::whereIn('tag_id' , $tagIds)->get();
            $goodsTags->each(function($goodsTag) use ($goodsTagsTranslations){
                $goodsTag->translations = $goodsTagsTranslations->where('tag_id' , $goodsTag->id);
            });
            $data = $goodsTags->toArray();
            Redis::set($key , \json_encode($data , JSON_UNESCAPED_UNICODE));
            Redis::expire($key , 60*60*24);
        }
        return AnonymousCollection::collection(collect($data));
    }

}
