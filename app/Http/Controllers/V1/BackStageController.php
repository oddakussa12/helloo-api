<?php

namespace App\Http\Controllers\V1;


use App\Models\User;
use App\Models\UserScore;
use Carbon\Carbon;
use App\Custom\RedisList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;


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









}
