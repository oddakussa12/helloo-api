<?php

namespace App\Http\Controllers\V1;


use Carbon\Carbon;
use App\Custom\RedisList;
use Illuminate\Http\Request;
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
