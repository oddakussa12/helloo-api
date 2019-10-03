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
            429, trans('auth.throttle_limit'), null, $headers
        );
    }
}
