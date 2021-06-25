<?php

namespace App\Http\Controllers\V1\Business;


use Illuminate\Http\Request;
use App\Models\Business\GoodsTag;
use App\Resources\AnonymousCollection;
use App\Http\Controllers\V1\BaseController;
use App\Models\Business\GoodsTagTranslation;

class GoodsTagController extends BaseController
{
    public function index(Request $request)
    {
        $locale = locale();
        $goodsTags = GoodsTag::all();
        $tagIds = $goodsTags->pluck('id')->toArray();
        $goodsTagsTranslations = GoodsTagTranslation::whereIn('tag_id' , $tagIds)->whereIn('locale' , array($locale , 'en'))->get();
        $goodsTags->each(function($goodsTag) use ($goodsTagsTranslations){
            $goodsTag->translation = $goodsTagsTranslations->where('tag_id' , $goodsTag->id)->get();
        });
        return AnonymousCollection::collection($goodsTags);
    }

}
