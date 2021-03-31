<?php

namespace App\Http\Controllers\V1;


use App\Custom\RedisList;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        $lastActivityTime = 'helloo:account:service:account-ry-last-activity-time';
        $chinaNow = Carbon::now('Asia/Shanghai')->startOfDay()->timestamp;
        $max = $request->input('max' , Carbon::now('Asia/Shanghai')->timestamp);
        $redis = new RedisList();
        $perPage = 10;
        $page = $request->input('page' , 1);
        $offset   = ($page-1)*$perPage;
        $users = $redis->zRevRangeByScore($lastActivityTime , $max , $chinaNow , true , array($offset , $perPage));
        return $this->response->array(array('users'=>$users , 'chinaTime'=>$chinaNow));
    }









}
