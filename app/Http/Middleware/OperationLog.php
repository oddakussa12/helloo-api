<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Carbon\Carbon;
use App\Models\OperationLog as Operation;
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
            $chinaNow = strtotime(Carbon::now('Asia/Shanghai'));
            $user_id = (int) auth()->id();
            $operation = Operation::where('user_id' , $user_id)
                                    ->whereDate('created_at' , '>=' , date('Y-m-d 00:00:00' , $chinaNow))
                                    ->whereDate('created_at' , '<=' , date('Y-m-d 23:59:59' , $chinaNow))
                                    ->exists();
            if(!$operation)
            {
                $log = new Operation();
                $log->setAttribute('user_id', $user_id);
                $log->setAttribute('ip', getRequestIpAddress());
                $log->setAttribute('referer', $request->server('HTTP_REFERER'));
                $log->save();
            }
        }
        return $next($request);
    }
}
