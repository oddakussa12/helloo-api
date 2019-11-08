<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
            $chinaNow = Carbon::now('Asia/Shanghai');
            $user_id = (int) auth()->id();
            DB::beginTransaction();
            try {
                $count = DB::table('views_logs')->where('user_id' , $user_id)
                    ->whereDate('created_at' , '>=' , date('Y-m-d 00:00:00' , strtotime($chinaNow)))
                    ->whereDate('created_at' , '<=' , date('Y-m-d 23:59:59' , strtotime($chinaNow)))
                    ->lockForUpdate()->count();
                if ($count <= 0) {
                    DB::table('views_logs')->insert(array(
                        'user_id'=>$user_id,
                        'ip'=>getRequestIpAddress(),
                        'referer'=>$request->server('HTTP_REFERER'),
                        'created_at'=>$chinaNow,
                    ));
                }
                // 提交事务
                DB::commit();
            } catch (\Exception $e) {
                // 回滚事务
                DB::rollBack();
            }
        }
        return $next($request);
    }
}
