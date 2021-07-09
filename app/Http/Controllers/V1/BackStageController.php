<?php

namespace App\Http\Controllers\V1;


use App\Repositories\Contracts\UserRepository;
use Carbon\Carbon;
use App\Models\User;
use App\Models\BlackUser;
use App\Models\UserScore;
use App\Custom\RedisList;
use Illuminate\Http\Request;
use App\Models\Business\Shop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Dingo\Api\Exception\ResourceException;


class BackStageController extends BaseController
{

    public function versionUpgrade()
    {
        $lastVersion = 'helloo:app:service:new-version';
        Redis::del($lastVersion);
        //backStage/version/upgrade
        return $this->response->noContent();
    }

    /**
     * @return mixed
     * 获取排行榜100
     */
    public function score()
    {
        $memKey = 'helloo:account:user-score-rank';
        $result = Redis::zrevrangebyscore($memKey, '+inf', '-inf', ['withScores'=>true, 'limit'=>[0,100]]);
        return $this->response->array($result);
    }

    /**
     * @param Request $request
     * 保存积分
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
    
    public function updateShop(Request $request , $id)
    {
        $fields  = array();
        $user    = User::where('user_id' , $id)->firstOrFail();
        $user_verified   = $request->input('user_verified');
        $user_delivery   = $request->input('user_delivery');
        if($user_verified!==null)
        {
            $fields['user_verified'] = intval($user_verified);
        }
        if($user_delivery!==null)
        {
            $fields['user_delivery'] = intval($user_delivery);
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
            $goodsCategory = DB::connection('lovbee')->table('goods_categories');
            $goodsCategory = $goodsCategory->where('user_id' , $id)->where('default' , 1)->first();
            if(empty($goodsCategory))
            {
                $data = array(
                    'category_id'=>$categoryId,
                    'user_id'=>$id,
                    'name'=>'default',
                    'default'=>1,
                    'created_at'=>$now,
                    'updated_at'=>$now,
                );
                DB::connection('lovbee')->table('goods_categories')->insert($data);
                Redis::del("helloo:business:goods:category:service:account:".$id);
            }
        }
        return $this->response->accepted();
    }

    public function storeShopTag(Request $request)
    {
        $tag = strtolower(strval($request->input('tag' , '')));
        $locale = (array)$request->input('locale' , array());
        $rules = array(
            'tag'=>[
                'bail',
                'required',
                'string',
                'min:1',
                'max:32',///^[_0-9a-z]{6,16}$/i
                'regex:/^[_a-z]{1,32}$/'
            ],
            'locale'=>[
                'bail',
                'required',
                'array'
            ],
        );
        $validationField = array(
            'tag' => $tag,
            'locale' => $locale,
        );
        Validator::make($validationField, $rules)->validate();

        $id = app('snowflake')->id();
        $data = array(
            'id'=>$id,
            'tag'=>$tag,
            'created_at'=>date('Y-m-d H:i:s'),
        );
        $locale = array_filter($locale , function($v , $k){
            return !empty($v)&&!empty($k);
        } , ARRAY_FILTER_USE_BOTH);
        $translations = array_map(function($v) use ($id , $locale){
            $content = $locale[$v];
            if(mb_strlen($content)>128)
            {
                abort(422 , 'Tag name is too long!');
            }
            return array(
                'id'=>app('snowflake')->id(),
                'tag_id'=>$id,
                'locale'=>$v,
                'tag_content'=>$locale[$v],
            );
        } , array_keys($locale));
        $shopTag = DB::table('shops_tags')->where('tag' , $tag)->first();
        if(!empty($shopTag))
        {
            abort(422 , 'Tag must be unique!');
        }
        try{
            DB::beginTransaction();
            $tagResult = DB::table('shops_tags')->insert($data);
            if(!$tagResult)
            {
                abort(500 , 'tag insert failed!');
            }
            $translationResult = DB::table('shops_tags_translations')->insert($translations);
            if(!$translationResult)
            {
                abort(500 , 'tag translation insert failed!');
            }
            DB::commit();
            Redis::del('helloo:business:service:shop:tags');
        }catch (\Exception $e)
        {
            DB::rollBack();
            Log::info('store_shop_tag_fail' , array(
                'message'=>$e->getMessage(),
                'data'=>$request->all(),
            ));
        }
        return $this->response->accepted();

    }


    public function updateShopTag(Request $request , $id)
    {
        $content = $request->input('content' , '');
        $locale = $request->input('locale' , '');
        $rules = array(
            'content'=>[
                'bail',
                'required',
                'string',
                'min:1',
                'max:32'
            ],
            'locale'=>[
                'bail',
                'required',
                'string',
                'min:2',
                'max:16'
            ],
        );
        $validationField = array(
            'content' => $content,
            'locale' => $locale,
        );
        Validator::make($validationField, $rules)->validate();
        DB::table('shops_tags_translations')->where('tag_id' , $id)->where('locale' , $locale)->update(array(
            'tag_content'=>$content
        ));
        Redis::del('helloo:business:service:shop:tags');
        return $this->response->accepted();
    }

}
