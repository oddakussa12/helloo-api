<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Repositories\Contracts\UserRepository;

class StatisticsController extends BaseController
{

    public function duration(Request $request)
    {
        $time = floatval($request->input('time' , 0));
        $target = intval($request->input('target_id' , 0));
        $status = strval($request->input('status' , 0));//initiative  passive  abnormal
        $type = strval($request->input('type' , 'video'));
        app(UserRepository::class)->findOrFail($target);
        DB::table('duration_statistics')->insert(
            array(
                'user_id'=>auth()->id(),
                'target_id'=>$target,
                'time'=>$time,
                'status'=>$status,
                'type'=>$type,
                'created_at'=>Carbon::now()->toDateTimeString(),
            )
        );
        return $this->response->created();
    }

    public function download(Request $request)
    {
        if($request->has('uuid'))
        {
            $uuid = substr(strval($request->input('uuid' , '')) , 0 , 64);
            $num = substr(strval($request->input('num' , '')) , 0 , 64);
            DB::table('logs')->insert(array('phone'=>$num , 'uuid'=>$uuid , 'created_at'=>Carbon::now()->toDateTimeString()));
        }
        return $this->response->created();
    }

    public function matchSucceed(Request $request , string $type)
    {
        $userId = auth()->id();
        DB::table('match_statistics')->insert(array(
            'user_id'=>$userId,
            'type'=>$type,
            'result'=>1,
            'created_at'=>Carbon::now()->toDateTimeString(),
        ));
        return $this->response->created();
    }

    public function matchFailed(Request $request , string $type)
    {
        $userId = auth()->id();
        DB::table('match_statistics')->insert(array(
            'user_id'=>$userId,
            'type'=>$type,
            'created_at'=>Carbon::now()->toDateTimeString(),
        ));
        return $this->response->created();
    }

    public function event(Request $request)
    {
        $event = $request->input('event' , '');
    }

    public function invitation(Request $request)
    {
        $beInvited = intval($request->input('user_id' , ''));
        if($beInvited>0)
        {
            $userId = auth()->id();
            DB::table('invitation_statistics')->insert(array(
                'invited'=>$beInvited,
                'user_id'=>$userId,
                'created_at'=>Carbon::now()->toDateTimeString(),
            ));
        }
        return $this->response->created();
    }

    public function log(Request $request)
    {
        if($request->has('log'))
        {
            $all = $request->input('log');
        }else{
            $all = \json_encode($request->all());
        }
        $userId = intval(auth()->id());
        DB::table('logs')->insert(array('log'=>$all , 'user_id'=>$userId , 'ip'=>getRequestIpAddress(), 'created_at'=>Carbon::now()->toDateTimeString()));
        return $this->response->created();
    }

    public function videoRecord(Request $request)
    {
        $data = $request->input('data' , '');
        $jti = JWTAuth::getClaim('jti');
        $plaintext = opensslDecrypt($data , $jti);
        $data = \json_decode(strval($plaintext) , true);
        if(is_array($data)&&isset($data['from_id'])&&isset($data['user_id'])&&isset($data['msg_id']))
        {
            $agent = new Agent();
            $version = $agent->getHttpHeader('HellooVersion');
            DB::table('play_logs')->insert(array(
                'from_id'=>$data['from_id'] ,
                'to_id'=>$data['to_id'] ,
                'user_id'=>$data['user_id'] ,
                'msg_id'=>$data['msg_id'] ,
                'ip'=>getRequestIpAddress(),
                'version'=>empty($version)?0:$version,
                'created_at'=>Carbon::now()->toDateTimeString()
            ));
        }
        return $this->response->created();
    }

    public function uploadFail(Request $request)
    {
        $exception = strval($request->input('exception' , ''));
        $size = intval($request->input('size' , 0));
        $way = strval($request->input('way' , ''));
        $device = strval($request->input('device' , ''));
        DB::table('failed_uploads')->insert(array(
            'user_id'=>auth()->id(),
            'size'=>$size,
            'way'=>$way,
            'exception'=>$exception,
            'ip'=>getRequestIpAddress(),
            'device'=>$device,
            'failed_at'=>Carbon::now()->toDateTimeString(),
        ));
        return $this->response->created();
    }

    public function recordLog(Request $request)
    {
        $bundleName = strval($request->input('bundle_name' , ''));
        $time = intval($request->input('time' , 0));
        if($time>0)
        {
            $agent = new Agent();
            $version = $agent->getHttpHeader('HellooVersion');
            $index = Carbon::now('UTC')->format("Ym");
            DB::table('record_logs_'.$index)->insert(array(
                'user_id'=>auth()->id() ,
                'bundle_name'=>$bundleName ,
                'time'=>$time ,
                'ip'=>getRequestIpAddress(),
                'version'=>empty($version)?0:$version,
                'created_at'=>Carbon::now()->toDateTimeString()
            ));
        }
        return $this->response->created();
    }
}
