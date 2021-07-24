<?php

namespace App\Http\Controllers\V1\Business;


use Illuminate\Http\Request;
use App\Models\Business\Goods;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Models\Business\GoodsCategory;
use App\Resources\AnonymousCollection;
use App\Http\Controllers\V1\BaseController;
use App\Http\Requests\StoreGoodsCategoryRequest;
use App\Http\Requests\UpdateGoodsCategoryRequest;

class GoodsCategoryController extends BaseController
{
    /**
     * @note 商家商品类别
     * @datetime 2021-07-12 17:50
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $goodsCategories = GoodsCategory::where('user_id' , $userId)->select('category_id' , 'name' , 'default' , 'sort')->orderByDesc('default')->orderByDesc('sort')->get();
        return AnonymousCollection::collection($goodsCategories);
    }

    /**
     * @note 商家商品类别新增
     * @datetime 2021-07-12 17:50
     * @param StoreGoodsCategoryRequest $request
     * @return AnonymousCollection
     */
    public function store(StoreGoodsCategoryRequest $request)
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
            abort(500 , 'Failed to add goods category!');
        }
        $goodsCategory = GoodsCategory::where('category_id' , $id)->select('category_id' , 'name' , 'default' , 'sort')->first();
        if(!empty($goodsIds))
        {
            $goods = $goods->each(function ($g) use ($numberGoodsIds){
                $g->sort = $numberGoodsIds[$g->id];
            })->sortByDesc('sort')->values();
            $goodsCategory->goods = AnonymousCollection::collection($goods);
        }
        return new AnonymousCollection($goodsCategory);
    }

    /**
     * @note 商家商品类别更新
     * @datetime 2021-07-12 17:51
     * @param UpdateGoodsCategoryRequest $request
     * @param $id
     * @return \Dingo\Api\Http\Response
     */
    public function update(UpdateGoodsCategoryRequest $request , $id)
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
        if(empty($goodsCategory)||$goodsCategory->user_id!=$userId)
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
                if(!$goodsCategory->is_default)
                {
                    DB::table('goods')->whereIn('id' , $goodsIds)->update(
                        array('discounted_price'=>-1)
                    );
                }
            }else{
                $goodsCategoryData = array(
                    'goods_num' => 0,
                    'sort'=>$sort,
                    'updated_at'=>$now,
                );
            }
            !empty($name)&&!$goodsCategory->is_default&&$goodsCategoryData['name']=$name;
            $goodsCateGoryResult = DB::table('goods_categories')->where('category_id' , $categoryId)->update($goodsCategoryData);
            if($goodsCateGoryResult<=0)
            {
                abort(500 , 'goods category update failed!');
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

    /**
     * @note 商家商品类别排序更新
     * @datetime 2021-07-12 17:51
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function sort(Request $request)
    {
        $categoryIds = (array)$request->input('category_id');
        $user = auth()->user();
        $userId = $user->user_id;
        $goodsCategories = GoodsCategory::whereIn('category_id' , array_keys($categoryIds))->get();
        $goodsCategories->filter(function ($goodsCategory) use ($userId) {
            return !$goodsCategory->is_default&&$goodsCategory->user_id!=$userId;
        });
        if(empty($goodsCategories))
        {
            abort(422 , 'Categories does not exist!');
        }
        $now = date('Y-m-d H:i:s');
        $data = $goodsCategories->map(function ($goodsCategory, $key) use ($now , $categoryIds) {
            return array(
                'category_id'=>$goodsCategory->category_id,
                'sort'=>intval($categoryIds[$goodsCategory->category_id]),
                'updated_at'=>$now,
            );
        })->toArray();
        if(!empty($data))
        {
            $where = array('category_id'=>collect($data)->pluck('category_id')->toArray());
            $update = array('sort'=>collect($data)->pluck('sort')->toArray(),'updated_at'=>collect($data)->pluck('updated_at')->toArray());
            $condition = batchUpdate('t_goods_categories' , $where , $update);
            $sql = $condition['sql']." WHERE `category_id` in (".trim(implode(',' , $where['category_id']) , ',').")";
            DB::update($sql , $condition['building']);
            $key = "helloo:business:goods:category:service:account:".$userId;
            Redis::del($key);
        }
        return $this->response->accepted();
    }

    /**
     * @note 商家商品类别删除
     * @datetime 2021-07-12 17:51
     * @param $id
     * @return \Dingo\Api\Http\Response
     */
    public function destroy($id)
    {
        $userId = auth()->id();
        $goodsCategory = GoodsCategory::where('category_id' , $id)->firstOrFail();
        if($goodsCategory->goods_num>0||$goodsCategory->is_default||$goodsCategory->user_id!=$userId)
        {
            abort(422 , 'This category cannot be deleted!');
        }
        DB::table('goods_categories')->where('category_id' , $id)->delete();
        DB::table('categories_goods')->where('category_id' , $id)->delete();
        Redis::del("helloo:business:goods:category:service:account:".$userId);
        return $this->response->noContent();
    }

}
