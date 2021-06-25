<?php

namespace App\Http\Controllers\V1\Business;


use Illuminate\Http\Request;
use App\Models\Business\Goods;
use Illuminate\Support\Facades\DB;
use App\Models\Business\GoodsCategory;
use App\Resources\AnonymousCollection;
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
        $numberGoodsIds = (array)$request->input('goods_id' , array());
        $goodsIds = array_keys($numberGoodsIds);
        $goodsStatus = array();
        if(!empty($goodsIds))
        {
            $goods = Goods::whereIn('id' , $goodsIds)->get();
            $goodsStatus = $goods->pluck('status' , 'id')->toArray();
            $goodsIds = $goods->pluck('goods_id')->toArray();
        }
        if(!empty($goodsIds))
        {
            $now = date('Y-m-d H:i:s');
            $id = app('snowflake')->id();
            $data = array(
                'category_id'=>$id,
                'user_id'=>$userId,
                'name'=>$name,
                'goods_num'=>count($goodsIds),
                'created_at'=>$now,
            );
            DB::table('goods_categories')->insert($data);
            $categoryGoodsData = array_map(function($v , $k) use ($id , $userId , $now , $numberGoodsIds , $goodsStatus){
                return array(
                    'category_id'=>$id,
                    'goods_id'=>$v,
                    'user_id'=>$userId,
                    'status'=>isset($goodsStatus[$v])&&$goodsStatus[$v]==1,
                    'sort'=>intval($numberGoodsIds[$v]),
                    'created_at'=>$now,
                );
            } , $goodsIds);
            DB::table('categories_goods')->insert($categoryGoodsData);
        }
        return $this->response->created();
    }

    public function update(Request $request , $id)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $name = $request->input('name' , '');
        $categoryId = $id;
        $numberGoodsIds = (array)$request->input('goods_id' , array());
        $goodsIds = array_keys($numberGoodsIds);
        $goodsStatus = array();
        if(!empty($goodsIds))
        {
            $goods = Goods::whereIn('id' , $goodsIds)->get();
            $goodsStatus = $goods->pluck('status' , 'id')->toArray();
            $goodsIds = $goods->pluck('goods_id')->toArray();
        }
        $now = date('Y-m-d H:i:s');
        DB::beginTransaction();
        DB::table('categories_goods')->where('category_id' , $categoryId)->delete();
        if(!empty($goodsIds))
        {
            $categoryGoodsData = array_map(function($v , $k) use ($categoryId , $userId , $now , $numberGoodsIds , $goodsStatus){
                return array(
                    'category_id'=>$categoryId,
                    'goods_id'=>$v,
                    'user_id'=>$userId,
                    'status'=>isset($goodsStatus[$v])&&$goodsStatus[$v]==1,
                    'sort'=>intval($numberGoodsIds[$v]),
                    'created_at'=>$now,
                );
            } , $goodsIds);
            DB::table('categories_goods')->insert($categoryGoodsData);
            $goodsCategoryData = array(
                'goods_num' => count($categoryGoodsData),
            );
            !empty($name)&&$goodsCategoryData['name']=$name;
            DB::table('goods_categories')->where('category_id' , $categoryId)->update(array(
                'name'=>$name
            ));
        }
        DB::commit();
        return $this->response->accepted();
    }

    public function destroy($id)
    {
        $goodsCategory = DB::table('goods_categories')->where('category_id' , $id)->first();
        if($goodsCategory->goods_num>0)
        {
            abort(422 , 'There are goods in this category!');
        }
        DB::table('goods_categories')->where('category_id' , $id)->delete();
        return $this->response->noContent();
    }

}
