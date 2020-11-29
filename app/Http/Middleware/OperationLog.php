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
            $agent = new Agent();
            $chinaNow = Carbon::now('Asia/Shanghai');
            $time = strval($chinaNow->timestamp);
            $now = $chinaNow->format('Ymd');
            $version = $agent->getHttpHeader('HellooVersion');
            if($agent->match('HellooAndroid'))
            {
                $src = 'android';
            }elseif($agent->match('HellooiOS')){
                $src = 'ios';
            }else{
                $src = 'unknown';
            }
            $key = 'helloo:account:service:account-au'.date('Ymd' , $now);
            $user_id = (int) auth()->id();
            $route = $request->route()->getName();
            $data = array(
                'visited_at'=>$time,
                'user_id'=>$user_id,
                'referer'=>$src,
                'version'=>$version,
                'route'=>$route,
                'ip'=>getRequestIpAddress()
            );
            \Log::error($key);
            \Log::error($data);
            Redis::rpush($key."_op_list" , \json_encode($data , JSON_UNESCAPED_UNICODE));//20201017
            $lastActivityTime = 'helloo:account:service:account-ry-last-activity-time';;
            Redis::zadd($lastActivityTime , $time , intval(auth()->id()));
        }
        return $next($request);
    }
}
