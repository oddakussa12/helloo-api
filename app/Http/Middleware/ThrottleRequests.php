<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Routing\Middleware\ThrottleRequests as Throttle;

class ThrottleRequests extends Throttle
{
    protected function buildException($key, $maxAttempts)
    {
        $retryAfter = $this->getTimeUntilNextRetry($key);

        $headers = $this->getHeaders(
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts, $retryAfter),
            $retryAfter
        );
        return new HttpException(
            429, trans('auth.throttle' , ['seconds' => $headers['X-RateLimit-Remain-Time']]), null, $headers
        );
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
        if ($user = $request->user()) {
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
}
