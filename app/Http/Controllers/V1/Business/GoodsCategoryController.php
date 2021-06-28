<?php

namespace App\Http\Controllers\V1\Business;


use App\Models\Business\CategoryGoods;
use Illuminate\Http\Request;
use App\Models\Business\Goods;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Business\GoodsCategory;
use App\Resources\AnonymousCollection;
use App\Http\Controllers\V1\BaseController;
use Illuminate\Support\Facades\Redis;

class GoodsCategoryController extends BaseController
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $goodsCategories = GoodsCategory::where('user_id' , $userId)->select('category_id' , 'name' , 'default' , 'sort')->orderByDesc('default')->orderByDesc('sort')->get();
        return AnonymousCollection::collection($goodsCategories);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $name = $request->input('name' , '');
        $sort = intval($request->input('sort' , 0));
        $numberGoodsIds = (array)$request->input('goods_id' , array());
        $goodsIds = array_keys($numberGoodsIds);
        $goodsStatus = array();
        if(!empty($goodsIds))
        {
            $goods = Goods::whereIn('id' , $goodsIds)->get();
            $goods = $goods->reject(function ($g) use ($userId){
                return $g->user_id!=$userId;
            });
            $goodsStatus = $goods->pluck('status' , 'id')->toArray();
            $goodsIds = $goods->pluck('id')->toArray();
        }
        $now = date('Y-m-d H:i:s');
        $id = app('snowflake')->id();
        $data = array(
            'category_id'=>$id,
            'user_id'=>$userId,
            'name'=>$name,
            'sort'=>$sort,
            'goods_num'=>count($goodsIds),
            'created_at'=>$now,
            'updated_at'=>$now,
        );
        try{
            DB::beginTransaction();
            $goodsCateGoryResult = DB::table('goods_categories')->insert($data);
            if(!$goodsCateGoryResult)
            {
                abort(500 , 'goods category insert failed!');
            }
            if(!empty($goodsIds))
            {

                $categoryGoodsData = array_map(function($v) use ($id , $userId , $now , $numberGoodsIds , $goodsStatus){
                    return array(
                        'id'=>app('snowflake')->id(),
                        'category_id'=>$id,
                        'goods_id'=>$v,
                        'user_id'=>$userId,
                        'status'=>isset($goodsStatus[$v])&&$goodsStatus[$v]==1,
                        'sort'=>intval($numberGoodsIds[$v]),
                        'created_at'=>$now,
                    );
                } , $goodsIds);
                $cateGoryGoodsResult = DB::table('categories_goods')->insert($categoryGoodsData);
                if(!$cateGoryGoodsResult)
                {
                    abort(500 , 'category goods  insert failed!');
                }
            }
            DB::commit();
            Redis::del("helloo:business:goods:category:service:account:".$userId);
        }catch (\Exception $e)
        {
            DB::rollBack();
            Log::info('goods_category_store_fail' , array(
                'message'=>$e->getMessage(),
                'data'=>$request->all(),
                'user_id'=>$userId,
            ));
        }
        return $this->response->created();
    }

    public function update(Request $request , $id)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $name = $request->input('name' , '');
        $sort = intval($request->input('sort' , 0));
        $categoryId = $id;
        $numberGoodsIds = (array)$request->input('goods_id' , array());
        $goodsIds = array_keys($numberGoodsIds);
        $goodsStatus = array();
        $goodsCategory = GoodsCategory::where('category_id' , $categoryId)->first();
        if(empty($goodsCategory)||$goodsCategory->is_default||$goodsCategory->user_id!=$userId)
        {
            abort(422 , 'Category does not exist!');
        }
        if(!empty($goodsIds))
        {
            $goods = Goods::whereIn('id' , $goodsIds)->get();
            $goodsStatus = $goods->pluck('status' , 'id')->toArray();
            $goodsIds = $goods->pluck('id')->toArray();
        }
        $now = date('Y-m-d H:i:s');
        try{
            DB::beginTransaction();
            DB::table('categories_goods')->where('category_id' , $categoryId)->delete();
            if(!empty($goodsIds))
            {
                $categoryGoodsData = array_map(function($v) use ($categoryId , $userId , $now , $numberGoodsIds , $goodsStatus){
                    return array(
                        'id'=>app('snowflake')->id(),
                        'category_id'=>$categoryId,
                        'goods_id'=>$v,
                        'user_id'=>$userId,
                        'status'=>isset($goodsStatus[$v])&&$goodsStatus[$v]==1,
                        'sort'=>intval($numberGoodsIds[$v]),
                        'created_at'=>$now,
                    );
                } , $goodsIds);
                $cateGoryGoodsResult = DB::table('categories_goods')->insert($categoryGoodsData);
                if(!$cateGoryGoodsResult)
                {
                    abort(500 , 'category goods  insert failed!');
                }
                $goodsCategoryData = array(
                    'goods_num' => count($categoryGoodsData),
                    'sort'=>$sort,
                    'updated_at'=>$now,
                );
                !empty($name)&&$goodsCategoryData['name']=$name;
                $goodsCateGoryResult = DB::table('goods_categories')->where('category_id' , $categoryId)->update($goodsCategoryData);
                if($goodsCateGoryResult<=0)
                {
                    abort(500 , 'goods category update failed!');
                }
            }else{
                $goodsCategoryData = array(
                    'goods_num' => 0,
                    'sort'=>$sort,
                    'updated_at'=>$now,
                );
                !empty($name)&&$goodsCategoryData['name']=$name;
                $goodsCateGoryResult = DB::table('goods_categories')->where('category_id' , $categoryId)->update($goodsCategoryData);
                if($goodsCateGoryResult<=0)
                {
                    abort(500 , 'goods category update failed!');
                }
            }
            DB::commit();
            Redis::del("helloo:business:goods:category:service:account:".$userId);
        }catch (\Exception $e)
        {
            DB::rollBack();
            Log::info('goods_category_update_fail' , array(
                'message'=>$e->getMessage(),
                'data'=>$request->all(),
                'user_id'=>$userId,
            ));
        }
        return $this->response->accepted();
    }

    public function destroy($id)
    {
        $userId = auth()->id();
        $goodsCategory = GoodsCategory::where('category_id' , $id)->first();
        if(empty($goodsCategory)||$goodsCategory->goods_num>0||$goodsCategory->is_default||$goodsCategory->user_id!=$userId)
        {
            abort(422 , 'This category cannot be deleted!');
        }
        DB::table('goods_categories')->where('category_id' , $id)->delete();
        Redis::del("helloo:business:goods:category:service:account:".$userId);
        return $this->response->noContent();
    }

}
