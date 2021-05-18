<?php
namespace App\Http\Middleware;

use Closure;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Redis;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class Blacklist extends BaseMiddleware
{
    public function handle($request, Closure $next)
    {
        if(auth()->check())
        {
            $userId = auth()->id();
            $key      = 'helloo:account:service:block-user';
            $time = Redis::zscore($key , $userId);
            if(!empty($time)&&time()-$time<=43200*60)
            {
                abort('401' , trans('auth.user_banned'));
            }
            $deviceId = (new Agent())->getHttpHeader('deviceId');
            if(!empty($deviceId))
            {
                $time = Redis::sismember('helloo:account:service:block-device' , $deviceId);
                if(!empty($time))
                {
                    abort(401 , trans('auth.user_device_banned'));
                }
            }
        }
        return $next($request);
    }
}