<?php

/*
 * This file is part of the godruoyi/php-snowflake.
 *
 * (c) Godruoyi <g@godruoyi.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace App\Custom\Uuid\Snowflake\Resolver;


use Illuminate\Support\Facades\Redis;
use Godruoyi\Snowflake\SequenceResolver;

class RedisSequenceResolver implements SequenceResolver
{
    /**
     * The cache prefix.
     *
     * @var string
     */
    protected $prefix;


    /**
     *  {@inheritdoc}
     */
    public function sequence(int $currentTime)
    {
        $lua = "return redis.call('exists',KEYS[1])<1 and redis.call('psetex',KEYS[1],ARGV[2],ARGV[1])";
        $key = $this->prefix.$currentTime;
        if (Redis::eval($lua, 1 , $key , 1 , 1000)) {
            return 0;
        }
        return Redis::incrby($key, 1);
    }

    /**
     * Set cacge prefix.
     * @param string $prefix
     * @return RedisSequenceResolver
     */
    public function setCachePrefix(string $prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }
}
