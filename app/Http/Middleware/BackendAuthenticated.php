<?php
namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class BackendAuthenticated extends BaseMiddleware
{
    public function handle($request, Closure $next)
    {
        $params = (array)$request->except('signature');
        $signature = strtolower(strval($request->input('signature' , '')));
        $app_signature = common_signature($params);
        if($signature!=$app_signature)
        {
//            abort(403 , __('Unauthorized'));
        }
        return $next($request);
    }
}