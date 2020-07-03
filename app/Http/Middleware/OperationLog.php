<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class OperationLog extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(auth()->check()&&$request->routeIs('post.index'))
        {
            $chinaNow = Carbon::now('Asia/Shanghai');
            $key = 'au'.date('Ymd' , strtotime($chinaNow)); //20191125
            $user_id = (int) auth()->id();
            if(!Redis::setbit($key , $user_id , 1))
            {
                $count = DB::table('views_logs')->where('user_id' , $user_id)
                    ->whereDate('created_at' , '>=' , date('Y-m-d 00:00:00' , strtotime($chinaNow)))
                    ->whereDate('created_at' , '<=' , date('Y-m-d 23:59:59' , strtotime($chinaNow)))
                    ->count();
                if ($count <= 0) {
                    DB::table('views_logs')->insert(array(
                        'user_id'=>$user_id,
                        'ip'=>getRequestIpAddress(),
                        'referer'=>$request->server('HTTP_REFERER'),
                        'created_at'=>$chinaNow,
                    ));
                }
            }

        }
        return $next($request);
    }
}
