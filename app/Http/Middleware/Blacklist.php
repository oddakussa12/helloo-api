<?php
namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class Blacklist extends BaseMiddleware
{
    public function handle($request, Closure $next)
    {
        return $next($request);
    }
}