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
    /**
     * @note 商家Tag
     * @datetime 2021-07-12 18:00
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $locale = locale();
        $key = 'helloo:business:service:shop:tags';
        if(Redis::exists($key))
        {
            $data = \json_decode(Redis::get($key , true));
        }else{
            $goodsTags = ShopTag::all();
            $tagIds = $goodsTags->pluck('id')->toArray();
            $goodsTagsTranslations = ShopTagTranslation::whereIn('tag_id' , $tagIds)->get();
            $goodsTags->each(function($goodsTag) use ($goodsTagsTranslations){
                $goodsTag->translations = $goodsTagsTranslations->where('tag_id' , $goodsTag->id)->values()->toArray();
            });
            $data = $goodsTags->toArray();
            Redis::set($key , \json_encode($data , JSON_UNESCAPED_UNICODE));
            Redis::expire($key , 60*60*24*7);
        }
        $locales = array();
        foreach ($data as $d)
        {
            $d = (array)$d;
            $translation = collect($d['translations'])->where('locale' , $locale)->first();
            if(blank($translation))
            {
                $translation = collect($d['translations'])->where('locale' , 'en')->first();
            }
            if(blank($translation))
            {
                continue;
            }else{
                $translation = (array)$translation;
                unset($d['translations']);
                $d['translation'] = $translation['tag_content'];
                array_push($locales , $d);
            }
        }
        return AnonymousCollection::collection(collect($locales));
    }

}
