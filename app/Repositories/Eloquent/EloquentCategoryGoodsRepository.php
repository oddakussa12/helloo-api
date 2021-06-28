<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 2019/5/19
 * Time: 18:35
 */
namespace App\Repositories\Eloquent;

use Illuminate\Support\Facades\Redis;
use App\Models\Business\GoodsCategory;
use App\Models\Business\CategoryGoods;
use App\Repositories\EloquentBaseRepository;
use App\Repositories\Contracts\CategoryGoodsRepository;

class EloquentCategoryGoodsRepository extends EloquentBaseRepository implements CategoryGoodsRepository
{
    public function findByUserId($userId)
    {
        $key = "helloo:business:goods:category:service:account:".$userId;
        if(Redis::exists($key))
        {
            $categories = \json_decode(Redis::get($key) , true);
        }else{
            $categoryGoods = CategoryGoods::where('user_id', $userId)->where('status' , 1)->get();
            $categories = GoodsCategory::where('user_id' , $userId)->get();
            $categories = $categories->map(function($category , $k) use ($categoryGoods){
                return array(
                    'category_id'=>$category->category_id,
                    'name'=>$category->name,
                    'default'=>intval($category->default),
                    'is_default'=>boolval($category->default),
                    'sort'=>$category->sort,
                    'goods_ids'=>$categoryGoods->where('category_id' , $category->category_id)->pluck('sort' , 'goods_id')->toArray()
                );
            })->toArray();
            Redis::set($key , \json_encode($categories,JSON_UNESCAPED_UNICODE));
            Redis::expire($key , 60*60*24*7);
        }
        return $categories;
    }
}
