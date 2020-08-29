<?php
namespace App\Http\Middleware;

use Closure;
use App\Custom\RedisList;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class RepeatedSubmit extends BaseMiddleware
{
    public function handle($request, Closure $next)
    {
        $routeName = $request->route()->uri();
        $params = $request->all();
        $redis = new RedisList();
        if(!blank($params))
        {
            $routeName = $routeName.'#'.http_build_query($params);
        }
        if(auth()->check())
        {
            $routeName = $routeName.'#'.auth()->id();
        }

        $routeName = md5($routeName);

        $lock = $redis->tryGetLock($routeName);

        if(!$lock)
        {
            abort(429 , trans('auth.throttle_limit'));
        }
        $next = $next($request);
        $redis->releaseLock($routeName);
        return $next;
    }

}