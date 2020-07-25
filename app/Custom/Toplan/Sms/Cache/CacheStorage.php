<?php
namespace App\Custom\Toplan\Sms\Cache;

use Toplan\Sms\Storage;
use Illuminate\Support\Facades\Redis;

class CacheStorage implements Storage
{
    protected static $lifetime = 1;

    public static function setMinutesOfLifeTime($time)
    {
        if (is_int($time) && $time > 0) {
            self::$lifetime = $time;
        }
    }

    public function set($key, $value)
    {
        Redis::set($key, $value);
        Redis::expire($key, ceil(self::$lifetime*60));
//        Cache::put($key, $value, self::$lifetime);
    }

    public function get($key, $default)
    {
        $value = Redis::get($key);
        $value = blank($value)?$default:$value;
        return $value;
//        return Cache::get($key, $default);
    }

    public function forget($key)
    {
        if (Redis::exists($key)) {
            Redis::del($key);
        }
//        if (Cache::has($key)) {
//            Cache::forget($key);
//        }
    }
}