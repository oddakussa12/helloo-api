<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use App\Models\User;
use App\Models\BlackUser;
use App\Models\UserScore;
use App\Custom\RedisList;
use Illuminate\Http\Request;
use App\Models\Business\Goods;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Models\Business\SpecialGoods;
use Illuminate\Support\Facades\Validator;
use App\Models\Business\DelaySpecialGoods;
use App\Jobs\DelaySpecialGoods as DelaySpecialGoodsJob;
use App\Repositories\Contracts\UserRepository;


class BackStageController extends BaseController
{
    /**
     * @note 清除版本升级缓存
     * @datetime 2021-07-12 18:47
     * @return \Dingo\Api\Http\Response
     */
    public function versionUpgrade()
    {
        $lastVersion = 'helloo:app:service:new-version';
        Redis::del($lastVersion);
        return $this->response->noContent();
    }

    /**
     * @note 获取 score top 100
     * @datetime 2021-07-12 18:47
     * @return mixed
     */
    public function score()
    {
        $memKey = 'helloo:account:user-score-rank';
        $result = Redis::zrevrangebyscore($memKey, '+inf', '-inf', ['withScores'=>true, 'limit'=>[0,100]]);
        return $this->response->array($result);
    }

    /**
     * @note 设置 score top 100
     * @datetime 2021-07-12 18:48
     * @param Request $request
     * @return void
     */
    public function storeScore(Request $request)
    {
        $userId = $request->input('id');
        $score  = $request->input('score');
        $time   = date('Y-m-d H:i:s');
        if ($score<=0) {
            return $this->response->errorBadRequest();
        }
        User::FindOrFail($userId);
        $uScore = UserScore::where('user_id', $userId)->first();

        $lScore = isset($uScore->score) ? $score - $uScore->score : $score;
        $hash   = hashDbIndex($userId);
        $type   = 'BackOperator';

        if ($lScore==0) {
            return $this->response->array([]);
        }
        $data = [
            'id'      => app('snowflake')->id(),
            'user_id' => $userId,
            'type'    => $type,
            'score'   => $lScore,
            'created_at' => $time,
        ];
        $insert = [
            'user_id'    => $userId,
            'score'      => $score,
            'created_at' => $time,
            'updated_at' => $time,
        ];
        try{
            DB::beginTransaction();
            $logResult = DB::table('users_scores_logs_'.$hash)->insert($data);
            if (!$logResult) {
                throw new \Exception('user score log insert fail');
            }
            if (blank($uScore)) {
                $scoreResult = DB::table('users_scores')->insert($insert);
            } else {
                $scoreResult = DB::table('users_scores')->where('user_id', $userId)->increment('score', $lScore, ['updated_at'=>$time]);
            }
            if (intval($scoreResult)<=0) {
                throw new \Exception('user score insert or update fail');
            }
            DB::commit();

            // 积分 排行
            $memKey = 'helloo:account:user-score-rank';
            Redis::zadd($memKey, $score, $userId);
            return $this->response->array([]);

        }catch (\Exception $e){
            DB::rollBack();
            Log::info($type , ['user_id'=>$userId, 'type'=>$type, 'message'=>$e->getMessage()]);
            return $this->response->errorBadRequest();
        }
    }

    /**
     * @note 最近上线
     * @datetime 2021-07-12 18:48
     * @param Request $request
     * @return mixed
     */
    public function lastOnline(Request $request)
    {
        $userId = strval($request->input('user_id' , ''));
        $chinaNow = Carbon::now('Asia/Shanghai')->startOfDay()->timestamp;
        $lastActivityTime = 'helloo:account:service:account-ry-last-activity-time';
        $perPage = 10;
        if(!blank($userId))
        {
            $userId = explode(',' , $userId);
            $users = array();
            Log::info('one' , $request->all());
            foreach ($userId as $id)
            {
                if(blank($id))
                {
                    continue;
                }
                $time = Redis::zscore($lastActivityTime , $id);
                $users[$id] = ($time===null||$time===false)?946656000:intval($time);
            }
            Log::info('$users' , $users);
            $count = count($users);
        }else{
            Log::info('two' , $request->all());
            $max = $request->input('max' , Carbon::now('Asia/Shanghai')->timestamp);
            $redis = new RedisList();
            $page = $request->input('page' , 1);
            $offset   = ($page-1)*$perPage;
            $users = $redis->zRevRangeByScore($lastActivityTime , $max , $chinaNow , true , array($offset , $perPage));
            $count = Redis::zcount($lastActivityTime , $chinaNow , $max);
        }
        return $this->response->array(array('users'=>$users , 'chinaTime'=>$chinaNow , 'count'=>$count , 'perPage'=>$perPage));
    }

    /**
     * @note 屏蔽设备
     * @datetime 2021-07-12 18:48
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function blockDevice(Request $request)
    {
        $userId = $request->input('user_id' , '');
        $deviceId = $request->input('device_id' , '');
        $rules = [
            'user_id' => [
                'bail',
                'required',
                'string'
            ],
            'device_id' => [
                'bail',
                'required',
                'string'
            ]
        ];
        $validationField = array(
            'user_id' => $userId,
            'device_id' => $deviceId,
        );
        Validator::make($validationField, $rules)->validate();
        $now = date('Y-m-d H:i:s');
        try{
            DB::beginTransaction();
            $result = DB::table('block_devices')->insert(array(
                'user_id'=>$userId,
                'device_id'=>$deviceId,
                'created_at'=>$now,
                'updated_at'=>$now,
            ));
            if(empty($result))
            {
                abort(405 , 'block device insert fail!');
            }
            $deviceKey      = 'helloo:account:service:block-device';
            Redis::sadd($deviceKey , $deviceId);
            DB::commit();

        }catch (\Exception $e){
            Log::info('block_device_fail' , array(
                'code'=>$e->getCode(),
                'message'=>$e->getMessage(),
            ));
            throw new StoreResourceFailedException('block device insert failed!');
        }
        return $this->response->accepted();
    }

    /**
     * @note 屏蔽用户
     * @datetime 2021-07-12 18:49
     * @param Request $request
     * @return void
     */
    public function blockUser(Request $request)
    {
        $key      = 'helloo:account:service:block-user';
        $userId   = $request->input('user_id' , 0);
        $operator = $request->input('operator' , '');
        $desc     = $request->input('desc' , '');
        $minute   = $request->input('minute' , 43200);
        if($userId<=0) {
            return $this->response->errorNotFound();
        }
        try {
            $start = date('Y-m-d H:i:s');
            $end   = date('Y-m-d H:i:s', time()+$minute*60);
            $res            = app('rcloud')->getUser()->Block()->add(array('id'=>$userId, 'minute'=>$minute));
            $res['userId']  = $userId;
            $res['minute']  = $minute;
            $res['message'] = 'ok';

            $blackUser = BlackUser::where('user_id' , $userId)->orderBy('updated_at' , "DESC")->first();
            if(blank($blackUser))
            {
                $insert = [
                    'user_id'=>$userId,
                    'desc'=>$desc,
                    'start_time'=>$start,
                    'end_time'=>$end,
                    'operator'=>$operator,
                    'created_at'=>$start,
                    'updated_at'=>$start,
                ];
                $data = BlackUser::insert($insert);
            }else{
                $blackUser->start_time = $start;
                $blackUser->end_time = $end;
                $blackUser->save();
            }
            throw_if($res['code']!=200 , new \Exception('internal error'));
            Redis::zadd($key, time() , $userId);
        } catch (\Throwable $e) {
            Redis::zrem($key, $userId);
            $res = array(
                'code'    => $e->getCode(),
                'userId'  => $userId,
                'minute'  => $minute,
                'message' => $e->getMessage(),
            );
            Log::info('block_fail' , $res);
        }
        return $this->response->array($res);

    }

    /**
     * @note 评论审核
     * @datetime 2021-07-12 18:49
     * @param Request $request
     */
    public function reviewComment(Request $request)
    {
        $id = $request->input('id' ,'');
        $level = $request->input('level' ,0);
        $reviewer = $request->input('reviewer' ,0);
        $comment = DB::table('comments')->where('comment_id' , $id)->first();
        if(!empty($comment)&&$comment->verified!=1&&$comment->type=='comment')
        {
            $point = $comment->point;
            $quality = $comment->quality;
            $service = $comment->service;
            $pointInterval = array(1 , 2 , 3 , 4, 5);
            $qualityOrServiceInterval = array(-1 , 0.0 , 0.5 , 1.0 , 1.5 ,2.0 , 2.5 , 3.0 , 3.5 , 4.0 , 4.5 , 5.0);
            if(in_array($point , $pointInterval)&&in_array($quality , $qualityOrServiceInterval)&&in_array($service , $qualityOrServiceInterval))
            {
                $goodsEvaluationData  = $goodsInsertEvaluationData = $shopEvaluationData = $shopInsertEvaluationData = array();

                $goodsEvaluationData['point_'.$point] = DB::raw('`point_'.$point."`+1");
                $goodsInsertEvaluationData['point_'.$point] = 1;

                $shopEvaluationData['point_'.$point] = DB::raw('`point_'.$point."`+1");
                $shopInsertEvaluationData['point_'.$point] = 1;

                if($quality!=-1)
                {
                    $shopEvaluationData['quality'] = DB::raw('`quality`+'.$quality);
                    $shopInsertEvaluationData['quality'] = $quality;
                }

                if($service!=-1)
                {
                    $shopEvaluationData['service'] = DB::raw('`service`+'.$service);
                    $shopInsertEvaluationData['service'] = $service;
                }

                $goodsEvaluation = DB::table('goods_evaluation_points')->where('goods_id' , $comment->goods_id)->first();
                $shopEvaluation = DB::table('shop_evaluation_points')->where('user_id' , $comment->owner)->first();
                $now = date('Y-m-d H:i:s');
                try{
                    DB::beginTransaction();
                    $commentResult = DB::table('comments')->where('comment_id' , $id)->update(array(
                        'verified'=>1,
                        'level'=>$level,
                        'reviewer'=>$reviewer,
                        'verified_at'=>$now,
                    ));
                    if($commentResult<1)
                    {
                        abort('500' , 'comment update failed!');
                    }
                    $goodsResult = DB::table('goods')->where('id' , $comment->goods_id)->update(array(
                        'comment'=>DB::raw('`comment`+1'),
                        'point'=>DB::raw('`point`+'.$point),
                        'quality'=>DB::raw('`quality`+'.$quality),
                        'service'=>DB::raw('`service`+'.$service)
                    ));
                    if($goodsResult<1)
                    {
                        abort('500' , 'goods update failed!');
                    }
                    $goodsInsertEvaluationData['updated_at'] = $goodsEvaluationData['updated_at'] = $now;
                    if(empty($goodsEvaluation))
                    {
                        $goodsInsertEvaluationData['id'] = app('snowflake')->id();
                        $goodsInsertEvaluationData['updated_at'] = $now;
                        $goodsInsertEvaluationData['goods_id'] = $comment->goods_id;
                        $goodsInsertEvaluationData['owner'] = $comment->owner;
                        $goodsInsertEvaluationData['created_at'] = $now;
                        $goodsEvaluationResult = DB::table('goods_evaluation_points')->insert($goodsInsertEvaluationData);
                    }else{
                        $goodsEvaluationResult = DB::table('goods_evaluation_points')->where('goods_id' , $comment->goods_id)->update($goodsEvaluationData);
                    }

                    if(empty($shopEvaluation))
                    {
                        $shopInsertEvaluationData['id'] = app('snowflake')->id();
                        $shopInsertEvaluationData['user_id'] = $comment->owner;
                        $shopInsertEvaluationData['created_at'] = $now;
                        $shopInsertEvaluationData['updated_at'] = $now;
                        $shopEvaluationResult = DB::table('shop_evaluation_points')->insert($shopInsertEvaluationData);
                    }else{
                        $shopEvaluationData['updated_at'] = $now;
                        $shopEvaluationResult = DB::table('shop_evaluation_points')->where('user_id' , $comment->owner)->update($shopEvaluationData);
                    }
                    if(empty($goodsEvaluationResult)||empty($shopEvaluationResult))
                    {
                        abort('500' , 'shop or goods evaluation failed!');
                    }
                    DB::commit();
                    $key = "helloo:account:point:service:account:".$comment->owner;
                    Redis::del($key);
                }catch (\Exception $e)
                {
                    DB::rollBack();
                    $data = array(
                        'code'    => $e->getCode(),
                        'data'  => $request->all(),
                        'message' => $e->getMessage(),
                    );
                    Log::info('comment_review_fail' , $data);
                }
            }else{
                abort(500 , 'comment param abnormal!');
            }

        }
    }

    /**
     * @note 评论驳回
     * @datetime 2021-07-12 18:49
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function rejectComment(Request $request)
    {
        $id = $request->input('id' ,'');
        $reviewer = $request->input('reviewer' ,0);
        $comment = DB::table('comments')->where('comment_id' , $id)->first();
        if(!empty($comment)&&$comment->verified!=0&&$comment->type=='comment')
        {
            $now = date('Y-m-d H:i:s');
            if($comment->verified==-1)
            {
                try{
                    DB::beginTransaction();
                    $commentResult = DB::table('comments')->where('comment_id' , $id)->update(array(
                        'verified'=>0,
                        'reviewer'=>$reviewer,
                        'verified_at'=>$now,
                    ));
                    if($commentResult<1)
                    {
                        abort('500' , 'comment update failed!');
                    }
                    DB::commit();
                }catch (\Exception $e)
                {
                    DB::rollBack();
                    $data = array(
                        'code'    => $e->getCode(),
                        'data'  => $request->all(),
                        'message' => $e->getMessage(),
                    );
                    Log::info('comment_reject_fail' , $data);
                }
            }elseif($comment->verified==1){
                $point = $comment->point;
                $quality = $comment->quality;
                $service = $comment->service;
                $pointInterval = array(1 , 2 , 3 , 4, 5);
                $qualityOrServiceInterval = array(-1 , 0.0 , 0.5 , 1.0 , 1.5 ,2.0 , 2.5 , 3.0 , 3.5 , 4.0 , 4.5 , 5.0);
                if(in_array($point , $pointInterval)&&in_array($quality , $qualityOrServiceInterval)&&in_array($service , $qualityOrServiceInterval))
                {
                    $goodsEvaluationData = $shopEvaluationData = array();

                    $goodsEvaluationData['point_'.$point] = DB::raw('`point_'.$point."`-1");

                    $shopEvaluationData['point_'.$point] = DB::raw('`point_'.$point."`-1");

                    if($quality!=-1)
                    {
                        $shopEvaluationData['quality'] = DB::raw('`quality`-'.$quality);
                    }
                    if($service!=-1)
                    {
                        $shopEvaluationData['service'] = DB::raw('`service`-'.$service);
                    }
                    $goodsEvaluation = DB::table('goods_evaluation_points')->where('goods_id' , $comment->goods_id)->first();
                    $shopEvaluation = DB::table('shop_evaluation_points')->where('user_id' , $comment->owner)->first();
                    $now = date('Y-m-d H:i:s');
                    try{
                        DB::beginTransaction();
                        $commentResult = DB::table('comments')->where('comment_id' , $id)->update(array(
                            'verified'=>0,
                            'reviewer'=>$reviewer,
                            'verified_at'=>$now,
                        ));
                        if($commentResult<1)
                        {
                            abort('500' , 'comment update failed!');
                        }
                        $goodsResult = DB::table('goods')->where('id' , $comment->goods_id)->update(array(
                            'comment'=>DB::raw('`comment`-1'),
                            'point'=>DB::raw('`point`-'.$point),
                            'quality'=>DB::raw('`quality`-'.$quality),
                            'service'=>DB::raw('`service`-'.$service)
                        ));
                        if($goodsResult<1)
                        {
                            abort('500' , 'goods update failed!');
                        }
                        $shopEvaluationData['updated_at'] = $goodsEvaluationData['updated_at'] = $now;
                        if(!empty($goodsEvaluation))
                        {
                            $goodsEvaluationResult = DB::table('goods_evaluation_points')->where('goods_id' , $comment->goods_id)->update($goodsEvaluationData);
                        }

                        if(!empty($shopEvaluation))
                        {
                            $shopEvaluationResult = DB::table('shop_evaluation_points')->where('user_id' , $comment->owner)->update($shopEvaluationData);
                        }
                        if(empty($goodsEvaluationResult)||empty($shopEvaluationResult))
                        {
                            abort('500' , 'shop or goods evaluation failed!');
                        }
                        DB::commit();
                        $key = "helloo:account:point:service:account:".$comment->owner;
                        Redis::del($key);
                    }catch (\Exception $e)
                    {
                        DB::rollBack();
                        $data = array(
                            'code'    => $e->getCode(),
                            'data'  => $request->all(),
                            'message' => $e->getMessage(),
                        );
                        Log::info('comment_reject_fail' , $data);
                    }
                }else{
                    abort(500 , 'comment param abnormal!');
                }

            }
        }
        return $this->response->accepted();
    }

    /**
     * @note 商家更新
     * @datetime 2021-07-12 18:49
     * @param Request $request
     * @param $id
     * @return \Dingo\Api\Http\Response
     */
    public function updateShop(Request $request , $id)
    {
        $fields  = array();
        $user    = User::where('user_id' , $id)->firstOrFail();
        $user_verified   = $request->input('user_verified');
        $user_delivery   = $request->input('user_delivery');
        $user_tag   = $request->input('user_tag');
        if($user_verified!==null)
        {
            $fields['user_verified'] = intval($user_verified);
        }
        if($user_delivery!==null)
        {
            $fields['user_delivery'] = intval($user_delivery);
        }
        if($user_tag!==null)
        {
            $fields['user_tag'] = strval($user_tag);
        }
        if(empty($fields))
        {
            abort(422 , 'Parameter cannot be empty!');
        }
        app(UserRepository::class)->update($user , $fields);
        if(isset($fields['user_verified']) && $fields['user_verified'])
        {
            $now = date('Y-m-d H:i:s');
            $categoryId = app('snowflake')->id();
            $goodsCategory = DB::table('goods_categories');
            $goodsCategory = $goodsCategory->where('user_id' , $id)->where('default' , 1)->first();
            if(empty($goodsCategory))
            {
                $data = array(
                    'category_id'=>$categoryId,
                    'user_id'=>$id,
                    'name'=>'Discount',
                    'default'=>1,
                    'created_at'=>$now,
                    'updated_at'=>$now,
                );
                DB::table('goods_categories')->insert($data);
                Redis::del("helloo:business:goods:category:service:account:".$id);
            }
        }
        return $this->response->accepted();
    }

    /**
     * @note 刷新商家 Tag 缓存
     * @datetime 2021-07-12 18:50
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function refreshShopTag(Request $request)
    {
        Redis::del('helloo:business:service:shop:tags');
        return $this->response->accepted();
    }

    /**
     * @note 更新/新增/删除特价商品
     * @datetime 2021-08-02 16:58
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function updateSpecialGoods(Request $request)
    {
        $rules = [
            'id' => 'bail|required_if:type,update,destroy|string',
            'goods_id' => 'bail|required_if:type,store|string',
            'special_price' => 'bail|required_if:type,store,update|numeric|between:0,99999',
            'free_delivery' => [
                'bail',
                'required_if:type,store,update',
                Rule::in(array(0,1))
            ],
            'packaging_cost' => 'bail|required_if:type,store,update|numeric|between:0,100',
            'deadline' => [
                'bail',
                'required_if:type,store,update',
                'date_format:Y-m-d H:i:s',
                'after_or_equal:today',
            ],
            'type' => [
                'bail',
                'required',
                Rule::in(array('update','store' , 'destroy'))
            ],
            'admin_id' => [
                'bail',
                'required',
                'string',
            ],
        ];
        $this->validate($request , $rules);
        $type = $request->input('type' , '');
        $adminId = substr($request->input('admin_id' , '') , 0 , 10);
        $specialPrice = round(floatval($request->input('special_price' , 0)) , 2);
        $freeDelivery = intval($request->input('free_delivery' , 0));
        $packagingCost = round(floatval($request->input('packaging_cost' , 0)) , 2);
        $deadline = strval($request->input('deadline' , ''));
        $now = date('Y-m-d H:i:s');
        if(in_array($type , array('store' , 'update'))&&date('Y-m-d H:i:s' , strtotime($deadline))!=$deadline)
        {
            abort(422 , 'deadline format error!');
        }
        if($type=='store')
        {
            $goodsId = $request->input('goods_id' , '');
            $goods = SpecialGoods::where('goods_id' , $goodsId)->first();
            if(!empty($goods))
            {
                abort(422 , 'Goods already exists!');
            }
            $goods = Goods::where('id' , $goodsId)->firstOrFail();
            $key = "helloo:business:goods:service:special:".$goodsId;
            $result = DB::table('special_goods')->insert(array(
                'shop_id'=>$goods->user_id,
                'goods_id'=>$goodsId,
                'special_price'=>$specialPrice,
                'free_delivery'=>$freeDelivery,
                'packaging_cost'=>$packagingCost,
                'deadline'=>$deadline,
                'admin_id'=>$adminId,
                'created_at'=>$now,
                'updated_at'=>$now
            ));
            if(!$result)
            {
                abort(500 , 'special goods insert failed!');
            }
            Redis::hmset($key , array(
                'special_price'=>$specialPrice,
                'free_delivery'=>$freeDelivery,
                'packaging_cost'=>$packagingCost,
                'deadline'=>$deadline,
                'status'=>1,
            ));
            Redis::EXPIREAT($key , strtotime($deadline));
        }elseif ($type=='update')
        {
            $id = $request->input('id' , '');
            $goods = SpecialGoods::where('id' , $id)->firstOrFail();
            $goods = $goods->makeVisible(array('admin_id'));
            $data = $goods->toArray();
            $data['log_updated_at'] = $now;
            $key = "helloo:business:goods:service:special:".$goods->goods_id;
            try{
                DB::beginTransaction();
                $updateResult = DB::table('special_goods')->where('id' , $id)->update(array(
                    'special_price'=>$specialPrice,
                    'free_delivery'=>$freeDelivery,
                    'packaging_cost'=>$packagingCost,
                    'deadline'=>$deadline,
                    'admin_id'=>$adminId,
                    'updated_at'=>$now
                ));
                if($updateResult<=0)
                {
                    abort(500 , 'special goods update failed!');
                }
                $result = DB::table('special_goods_logs')->insert($data);
                if(!$result)
                {
                    abort(500 , 'special goods log insert failed!');
                }
                DB::commit();
                Redis::hmset($key , array(
                    'special_price'=>$specialPrice,
                    'free_delivery'=>$freeDelivery,
                    'packaging_cost'=>$packagingCost,
                    'deadline'=>$deadline,
                ));
                Redis::EXPIREAT($key , strtotime($deadline));
            }catch (\Exception $e)
            {
                DB::rollBack();
                Log::info('special_goods_update_fail' , array(
                    'message'=>$e->getMessage(),
                    'data'=>$request->all()
                ));
            }
        }elseif ($type=='destroy')
        {
            $id = $request->input('id' , '');
            $goods = SpecialGoods::where('id' , $id)->first();
            if(empty($goods))
            {
                abort(404);
            }
            $goods = $goods->makeVisible(array('admin_id'));
            $key = "helloo:business:goods:service:special:".$goods->goods_id;
            $data = $goods->toArray();
            $data['log_updated_at'] = $now;
            $ds = array($data);
            $data['admin_id'] = $adminId;
            array_push($ds , $data);
            try{
                DB::beginTransaction();
                $deleteResult = DB::table('special_goods')->where('id' , $id)->delete();
                if($deleteResult<=0)
                {
                    abort(500 , 'special goods delete failed!');
                }
                $result = DB::table('special_goods_logs')->insert($ds);
                if(!$result)
                {
                    abort(500 , 'special goods log insert failed!');
                }
                DB::commit();
                Redis::del($key);
            }catch (\Exception $e)
            {
                DB::rollBack();
                Log::info('special_goods_destroy_fail' , array(
                    'message'=>$e->getMessage(),
                    'data'=>$request->all()
                ));
            }
        }
        return $this->response->accepted();
    }

    /**
     * @note 更新特价活动图片
     * @datetime 2021-08-05 18:13
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function updateSpecialGoodsImage(Request $request)
    {
        $image = $request->input('image' , '');
        if(filter_var($image, FILTER_VALIDATE_URL) !== false)
        {
            $key = 'helloo:business:special_goods:image';
            Redis::set($key , $image);
        }
        return $this->response->accepted();
    }

    /**
     * @note 更新特价活动开关
     * @datetime 2021-08-05 18:13
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function updateGoodsDiscountedSwitch(Request $request)
    {
        $switch = intval($request->input('switch' , 0));
        Redis::set("helloo:business:order:service:discounted:switch" , $switch);
        return $this->response->accepted();
    }

    /**
     * @note 更新/新增/删除特价延迟商品
     * @datetime 2021-08-02 16:58
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function updateDelaySpecialGoods(Request $request)
    {
        $rules = [
            'id' => 'bail|required_if:type,update,destroy|string',
            'goods_id' => 'bail|required_if:type,store|string',
            'special_price' => 'bail|required_if:type,store,update|numeric|between:0,99999',
            'free_delivery' => [
                'bail',
                'required_if:type,store,update',
                Rule::in(array(0,1))
            ],
            'packaging_cost' => 'bail|required_if:type,store,update|numeric|between:0,100',
            'start_time' => [
                'bail',
                'required_if:type,store,update',
                'date_format:Y-m-d H:i:s',
                'after_or_equal:today',
            ],
            'deadline' => [
                'bail',
                'required_if:type,store,update',
                'date_format:Y-m-d H:i:s',
                'after_or_equal:today',
            ],
            'type' => [
                'bail',
                'required',
                Rule::in(array('update','store' , 'destroy'))
            ],
            'admin_id' => [
                'bail',
                'required',
                'string',
            ],
        ];
        $this->validate($request , $rules);
        $type = $request->input('type' , '');
        $adminId = substr($request->input('admin_id' , '') , 0 , 10);
        $specialPrice = round(floatval($request->input('special_price' , 0)) , 2);
        $freeDelivery = intval($request->input('free_delivery' , 0));
        $packagingCost = round(floatval($request->input('packaging_cost' , 0)) , 2);
        $start_time = strval($request->input('start_time' , ''));
        $deadline = strval($request->input('deadline' , ''));
        $now = date('Y-m-d H:i:s');
        if(in_array($type , array('store' , 'update'))&&date('Y-m-d H:i:s' , strtotime($deadline))!=$deadline)
        {
            abort(422 , 'deadline format error!');
        }
        if(in_array($type , array('store' , 'update'))&&date('Y-m-d H:i:s' , strtotime($start_time))!=$start_time)
        {
            abort(422 , 'start time format error!');
        }
        if($type=='store')
        {
            $goodsId = $request->input('goods_id' , '');
            $goods = SpecialGoods::where('goods_id' , $goodsId)->first();
            if(!empty($goods))
            {
                abort(422 , 'Goods already exists!');
            }
            $goods = Goods::where('id' , $goodsId)->firstOrFail();
            $data = array(
                'special_price'=>$specialPrice,
                'free_delivery'=>$freeDelivery,
                'packaging_cost'=>$packagingCost,
                'deadline'=>$deadline,
                'start_time'=>$start_time,
            );
            $delaySpecialGoods = new DelaySpecialGoodsJob($data);
            $this->dispatch($delaySpecialGoods->onQueue('helloo_{delay_special_goods}')->delay(120));
            $jobId = $delaySpecialGoods->job->getJobId();
            $data['shop_id'] = $goods->user_id;
            $data['goods_id'] = $goodsId;
            $data['admin_id'] = $adminId;
            $data['delay_id'] = $jobId;
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
            $result = DB::table('delay_delay_special_goods')->insert($data);
            if(!$result)
            {
                abort(500 , 'special goods insert failed!');
            }
        }elseif ($type=='update')
        {
            $id = $request->input('id' , '');
            DelaySpecialGoods::where('id' , $id)->firstOrFail();
            $data = array(
                'special_price'=>$specialPrice,
                'free_delivery'=>$freeDelivery,
                'packaging_cost'=>$packagingCost,
                'deadline'=>$deadline,
                'start_time'=>$start_time,
            );
            $delaySpecialGoods = new DelaySpecialGoodsJob($data);
            $this->dispatch($delaySpecialGoods->onQueue('helloo_{delay_special_goods}')->delay(120));
            $jobId = $delaySpecialGoods->job->getJobId();
            $data['admin_id'] = $adminId;
            $data['delay_id'] = $jobId;
            $data['updated_at'] = $now;
            try{
                DB::beginTransaction();
                $updateResult = DB::table('delay_special_goods')->where('id' , $id)->update($data);
                if($updateResult<=0)
                {
                    abort(500 , 'delay special goods update failed!');
                }
                DB::commit();
            }catch (\Exception $e)
            {
                DB::rollBack();
                Log::info('delay_special_goods_update_fail' , array(
                    'message'=>$e->getMessage(),
                    'data'=>$request->all()
                ));
            }
        }elseif ($type=='destroy')
        {
            $id = $request->input('id' , '');
            $goods = DelaySpecialGoods::where('id' , $id)->first();
            if(empty($goods))
            {
                abort(404);
            }
            try{
                DB::beginTransaction();
                $deleteResult = DB::table('delay_special_goods')->where('id' , $id)->delete();
                if($deleteResult<=0)
                {
                    abort(500 , 'delay special goods delete failed!');
                }
                DB::commit();
                Redis::del($goods->delay_id);
            }catch (\Exception $e)
            {
                DB::rollBack();
                Log::info('delay_special_goods_destroy_fail' , array(
                    'message'=>$e->getMessage(),
                    'data'=>$request->all()
                ));
            }
        }
        return $this->response->accepted();
    }

}
