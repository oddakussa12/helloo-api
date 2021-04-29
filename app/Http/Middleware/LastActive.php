<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class LastActive extends BaseMiddleware
{
    public function handle($request, Closure $next)
    {
        if(auth()->check())
        {
            $time = time();
            $lastActivityTime = 'helloo:account:service:account-ry-last-activity-time';
            Redis::zadd($lastActivityTime , $time , intval(auth()->id()));
        }
        return $next($request);
    }
}