<?php

namespace App\Http\Controllers\V1\Business;


use App\Resources\AnonymousCollection;
use Illuminate\Http\Request;
use App\Models\Business\Goods;
use Illuminate\Support\Facades\DB;
use App\Models\Business\GoodsCategory;
use App\Http\Controllers\V1\BaseController;

class GoodsCategoryController extends BaseController
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $goodsCategories = GoodsCategory::where('user_id' , $userId)->get();
        return AnonymousCollection::collection($goodsCategories);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $name = $request->input('name' , '');
        $goodsIds = (array)$request->input('goods_id' , array());
        if(!empty($goodsIds))
        {
            $goods = Goods::whereIn('id' , $goodsIds)->get();
            $goodsIds = $goods->pluck('goods_id')->toArray();
        }
        $now = date('Y-m-d H:i:s');
        $id = app('snowflake')->id();
        $data = array(
            'category_id'=>$id,
            'user_id'=>$userId,
            'name'=>$name,
            'created_at'=>$now,
        );
        DB::table('goods_categories')->insert($data);
        if(!empty($goodsIds))
        {
            $categoryGoodsData = array_map(function($v , $k) use ($id , $userId , $now){
                return array(
                    'category_id'=>$id,
                    'goods_id'=>$v,
                    'user_id'=>$userId,
                    'created_at'=>$now,
                );
            } , $goodsIds);
            DB::table('categories_goods')->insert($categoryGoodsData);
        }
        return $this->response->created();
    }

    public function update(Request $request , $id)
    {
        $categoryId = $request->input('category_id' , '');
        $goodsIds = (array)$request->input('goods_id' , array());

    }
}
