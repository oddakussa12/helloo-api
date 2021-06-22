<?php

namespace App\Traits;

use Illuminate\Support\Facades\Redis;

trait CachableUser
{
    public function isBlocked($userId)
    {
        $key = 'helloo:account:service:block-user';
        $time = Redis::zscore($key , $userId);
        return !blank($time)&&time()-$time<=43200*60;
    }
}
