<?php

namespace App\Http\Controllers\V1;


use App\Models\BlackUser;
use App\Models\User;
use App\Models\UserScore;
use Carbon\Carbon;
use App\Custom\RedisList;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;


class BackStageController extends BaseController
{


    public function index()
    {

    }

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
                $users[$id] = $time==null?946656000:intval($time);
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
            $deviceKey      = 'block_device';
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
        $key      = 'block_user';
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

            Redis::zadd($key, time(), $userId);
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
        } catch (\Throwable $e) {
            Redis::zRem($key, $userId);
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









}
