<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Carbon\Carbon;
use Jenssegers\Agent\Agent;
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
        if(auth()->check())
        {
            $chinaNow = Carbon::now('Asia/Shanghai');
            $time = strval($chinaNow->timestamp);
            $now = $chinaNow->format('Ymd');
            $key = 'helloo:account:service:account-au'.date('Ymd' , $now);
            $user_id = (int) auth()->id();
            Redis::rpush($key."_op_list" , strval($user_id).'.'.$time);//20201017
            $lastActivityTime = 'helloo:account:service:account-ry-last-activity-time';;
            Redis::zadd($lastActivityTime , $time , intval(auth()->id()));
        }
        return $next($request);
    }
}
