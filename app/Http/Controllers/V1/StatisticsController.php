<?php

namespace App\Http\Controllers\V1;

use App\Repositories\Contracts\UserRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends BaseController
{

    public function duration(Request $request)
    {
        \Log::error($request->all());
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
}
