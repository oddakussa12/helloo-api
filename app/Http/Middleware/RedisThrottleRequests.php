<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Routing\Middleware\ThrottleRequests as Throttle;

class RedisThrottleRequests extends Throttle
{
    protected $now;

    public function handle($request, Closure $next, $maxAttempts = 1, $decayMinutes = 1)
    {
        $decaySeconds = ceil(intval($decayMinutes*60));

        $key = $this->resolveRequestSignature($request);

        $usedAttempts = $this->calculateUsedAttempts($key , $decaySeconds);

        $remainingAttempts = intval($maxAttempts-$usedAttempts['count']);

        $remainedAttempts = $remainingAttempts<0?0:$remainingAttempts;

        if($remainingAttempts<0)
        {
            $remainingTimes = intval($this->now-$usedAttempts['first']);
            $retryAfter = ceil(($decaySeconds*1000-$remainingTimes)/1000);
            throw $this->customizeBuildException($key, $maxAttempts , $remainedAttempts , $retryAfter);
        }

        $response = $next($request);

        return $this->addHeaders(
            $response, $maxAttempts,
            $remainedAttempts
        );
    }

    protected function customizeBuildException($key, $maxAttempts , $remainingAttempts , $retryAfter=null)
    {
//        $retryAfter = $this->getTimeUntilNextRetry($key);

        $headers = $this->getHeaders(
            $maxAttempts,
            $remainingAttempts,
            $retryAfter
        );

        return new HttpException(
            429, trans('auth.throttle_remain' , ['seconds' => $headers['X-RateLimit-Remain-Time']]), null, $headers
        );
    }


    /**
     * Calculate the number of remaining attempts.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  int|null  $retryAfter
     * @return int
     */
    protected function calculateRemainingAttempts($key, $maxAttempts, $retryAfter = null)
    {
        if (is_null($retryAfter)) {
            return $this->limiter->retriesLeft($key, $maxAttempts);
        }

        return 0;
    }


    protected function calculateUsedAttempts($key , $period)
    {
        $this->now = $now = millisecond();   # ???????????????
        $redis = Redis::connection('single');
        $redis->multi(); //????????????????????????
        $redis->zadd($key, $now, $now); //value ??? score ????????????????????????
        $redis->zremrangebyscore($key, 0, $now - $period * 1000); //???????????????????????????????????????????????????????????????????????????
        $redis->zcard($key);  //??????????????????????????????
        $redis->zrangebyscore($key , "-inf" , "+inf" , array(
            'withScores'=>true,
            'limit'=>array(0,1)
        ));
        $redis->expire($key, $period  + 1);  //????????????????????????
        $replies = $redis->exec();
        return array('count'=>intval($replies[2]) , 'first'=>array_first($replies[3]));
    }

    protected function resolveRequestSignature($request)
    {
        $route = $request->route();
        if(!$route)
        {
            throw new RuntimeException(
                'Unable to generate the request signature. Route unavailable.'
            );
        }
        $domain = $route->getDomain();
        $ip = getRequestIpAddress();
        $key = $domain.'|'.$ip;
        if ($user = $this->getAuth($request)) {
            $key .= '|'.$user->getAuthIdentifier();
        }
        if($routeName = $route->getName())
        {
            $key .= '|'.$routeName;
        }
        return sha1($key);
    }

    protected function getHeaders($maxAttempts, $remainingAttempts, $retryAfter = null)
    {
        $headers = [
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ];

        if (! is_null($retryAfter)) {
            $headers['Retry-After'] = $retryAfter;
            $headers['X-RateLimit-Reset'] = $this->availableAt($retryAfter);
            $headers['X-RateLimit-Remain-Time'] = $this->secondsUntil($retryAfter);
        }

        return $headers;
    }

    public function getAuth($request)
    {
        return $request->user();
    }
}
