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
use Illuminate\Support\Facades\Redis;

class EloquentGoodsRepository extends EloquentBaseRepository implements GoodsRepository
{

    public function storeLike(User $user , $goodsId)
    {
        $now = date('Y-m-d H:i:s');
        $userId = $user->getKey();
        $id = strval($userId).'-'.strval($goodsId);
        $like = DB::table('likes_goods')->where('id' , $id)->first();
        if(empty($like))
        {
            try{
                DB::beginTransaction();
                $likeGoodsResult = DB::table('likes_goods')->insert(array(
                    'id'=>$id,
                    'user_id'=>$userId,
                    'goods_id'=>$goodsId,
                    'created_at'=>$now,
                ));
                if(!$likeGoodsResult)
                {
                    abort(405 , 'goods like failed!');
                }
                $goodsResult = DB::table('goods')->where('id' , $goodsId)->increment('like' , 1 , array(
                    'liked_at'=>$now
                ));
                if($goodsResult<=0)
                {
                    abort(405 , 'goods update like failed!');
                }
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

    public function destroyLike(User $user , $goodsId)
    {
        $userId = $user->getKey();
        $id = strval($userId).'-'.strval($goodsId);
        $like = DB::table('likes_goods')->where('id' , $id)->first();
        if(!empty($like))
        {
            $now = date('Y-m-d H:i:s');
            try{
                DB::beginTransaction();
                $likeGoodsResult = DB::table('likes_goods')->where('id' , $like->id)->delete();
                if(!$likeGoodsResult)
                {
                    abort(405 , 'goods like failed!');
                }
                $goodsResult = DB::table('goods')->where('id' , $goodsId)->decrement('like');
                if($goodsResult<=0)
                {
                    abort(405 , 'goods update like failed!');
                }
                $data = collect($like)->toArray();
                $data['deleted_at'] = $now;
                DB::table('likes_goods_logs')->insert($data);
                DB::commit();
            }catch (\Exception $e)
            {
                DB::rollBack();
                Log::info('goods_delete_like_fail' , array(
                    'message'=>$e->getMessage(),
                    'user_id'=>$userId,
                    'goods_id'=>$goodsId,
                ));
            }
        }
    }

    public function like($goodsId)
    {
        $goodsLikes = DB::table('likes_goods')->where('goods_id' , $goodsId)->orderByDesc('created_at')->select('user_id','goods_id','created_at')->paginate(10);
        $goodsLikes->each(function($like){
            $like->user_id = strval($like->user_id);
        });
        return $goodsLikes;
    }


    public function findPointByGoodsId($goodsId)
    {
        $key = "helloo:business:point:service:goods:".$goodsId;
        $point = Redis::get($key);
        if(empty($point))
        {
            $goodsPoint = collect(DB::table('goods_evaluation_points')->where('goods_id' , $goodsId)->first());
            if(!blank($goodsPoint))
            {
                $point_1 = $goodsPoint->get('point_1' , 0);
                $point_2 = $goodsPoint->get('point_2' , 0);
                $point_3 = $goodsPoint->get('point_3' , 0);
                $point_4 = $goodsPoint->get('point_4' , 0);
                $point_5 = $goodsPoint->get('point_5' , 0);
                $pointNum = $point_1+$point_2+$point_3+$point_4+$point_5;
                $pointSum = $point_1+2*$point_2+3*$point_3+4*$point_4+5*$point_5;
                $data = collect(array(
                    'item'=>array(
                        'point_1'=>$point_1,
                        'point_2'=>$point_2,
                        'point_3'=>$point_3,
                        'point_4'=>$point_4,
                        'point_5'=>$point_5,
                    ),
                    'sum'=>$pointSum,
                    'num'=>$pointNum
                ));
            }else{
                $data = collect(array(
                    'item'=>array(
                        'point_1'=>0,
                        'point_2'=>0,
                        'point_3'=>0,
                        'point_4'=>0,
                        'point_5'=>0,
                    ),
                    'sum'=>0,
                    'num'=>0
                ));
            }
            $cache = $data->toArray();
            Redis::set($key , \json_encode($cache));
            Redis::expire($key , 60*60*24);
        }else{
            $data = collect(\json_decode($point , true));
        }
        $percentage = array();
        $item = $data->get('item' , array());
        $sum = $data->get('sum' , 0);
        $num = $data->get('num' , 0);
        foreach ($item as $k=>$i)
        {
            $numerator = $num>0?$i/$num*100:0;
            $percentage[$k] = round($numerator);
        }
        return array(
            'percentage'=>$percentage,
            'point'=>$num>0?round($sum/$num , 1):0.0,
            'comment'=>formatNumber($num),
        );
    }


}
