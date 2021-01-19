<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
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
        Log::info('download' , $request->all());
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
        $all = $request->all();
        DB::table('logs')->insert(array('log'=>\json_encode($all , JSON_UNESCAPED_UNICODE) , 'created_at'=>Carbon::now()->toDateTimeString()));
        return $this->response->created();
    }
}
