<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class Blacklist extends BaseMiddleware
{
    public function handle($request, Closure $next)
    {
        if(auth()->check())
        {
            $key      = 'block_user';
            $time = Redis::zscore($key , auth()->id());
            if(!blank($time)&&time()-$time>43200*60)
            {
                abort('401' , trans('auth.user_banned'));
            }
        }
        return $next($request);
    }
}