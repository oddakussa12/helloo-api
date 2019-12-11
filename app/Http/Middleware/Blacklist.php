<?php
namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class Blacklist extends BaseMiddleware
{
    public function handle($request, Closure $next)
    {
        $blacklist = 'blacklist/blacklist.json';
        if(\Storage::exists($blacklist)&&auth()->check())
        {
            $blacklist = \json_decode(\Storage::get($blacklist));
            $list = $blacklist->list;
            if(in_array(auth()->user()->user_name , $list))
            {
                abort(422 , __('Sorry, you are forbidden from accessing this page.'));
            }
        }
        return $next($request);
    }
}