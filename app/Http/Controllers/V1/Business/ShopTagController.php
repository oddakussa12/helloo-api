<?php

namespace App\Http\Controllers\V1\Business;


use Illuminate\Http\Request;
use App\Models\Business\ShopTag;
use App\Resources\AnonymousCollection;
use App\Http\Controllers\V1\BaseController;
use App\Models\Business\ShopTagTranslation;

class ShopTagController extends BaseController
{
    public function index(Request $request)
    {
        $locale = locale();
        $goodsTags = ShopTag::all();
        $tagIds = $goodsTags->pluck('id')->toArray();
        $goodsTagsTranslations = ShopTagTranslation::whereIn('tag_id' , $tagIds)->whereIn('locale' , array($locale , 'en'))->get();
        $goodsTags->each(function($goodsTag) use ($goodsTagsTranslations){
            $goodsTag->translations = $goodsTagsTranslations->where('tag_id' , $goodsTag->id);
        });
        return AnonymousCollection::collection($goodsTags);
    }

}
