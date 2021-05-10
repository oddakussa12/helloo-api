<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 2019/5/19
 * Time: 18:35
 */
namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\EloquentBaseRepository;
use App\Repositories\Contracts\GoodsRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EloquentGoodsRepository extends EloquentBaseRepository implements GoodsRepository
{

    public function storeLike(User $user , $goodsId)
    {
        $userId = $user->getKey();
        $id = strval($userId).'-'.strval($goodsId);
        $like = DB::table('likes_goods')->where('id' , $id)->first();
        if(empty($like))
        {
            try{
                DB::beginTransaction();
                DB::table('likes_goods')->insert(array(
                    'id'=>$id,
                    'user_id'=>$userId,
                    'goods_id'=>$goodsId,
                    'created_at'=>date('Y-m-d H:i:s'),
                ));
                DB::table('goods')->where('id' , $goodsId)->increment('like');
                DB::commit();
            }catch (\Exception $e)
            {
                DB::rollBack();
                Log::info('goods_like_fail' , array(
                    'message'=>$e->getMessage(),
                    'user_id'=>$userId,
                    'goods_id'=>$goodsId,
                ));
            }

        }
    }

    public function like($goodsId)
    {
        return DB::table('likes_goods')->where('goods_id' , $goodsId)->select('user_id','goods_id','created_at')->paginate(10);
    }

}
