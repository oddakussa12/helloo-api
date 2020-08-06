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
        if(auth()->check()&&($request->routeIs('post.index')||$request->routeIs('myself.update')))
        {
            $chinaNow = Carbon::now('Asia/Shanghai');
            $now = $chinaNow->toDateTimeString();
            $key = 'au'.date('Ymd' , strtotime($chinaNow)); //20191125
            $user_id = (int) auth()->id();
            if(!Redis::setbit($key , $user_id , 1))
            {
                $view = DB::table('views_logs')->where('user_id' , $user_id)->orderBy('id' , 'DESC')->first();
                if(empty($view)||Carbon::parse($view->created_at , 'Asia/Shanghai')->endOfDay()->timestamp<$chinaNow->endOfDay()->timestamp)
                {
                    $agent = new Agent();
                    if($agent->match('YooulAndroid'))
                    {
                        $referer = 'android';
                    }elseif ($agent->match('YoouliOS'))
                    {
                        $referer = 'ios';
                    }else{
                        $referer = $request->server('HTTP_REFERER');
                        $referer = empty($referer)?'web':$referer;
                    }
                    DB::table('views_logs')->insert(array(
                        'user_id'=>$user_id,
                        'ip'=>getRequestIpAddress(),
                        'referer'=>$referer,
                        'created_at'=>$now,
                    ));
                }
            }

        }
        return $next($request);
    }
}
