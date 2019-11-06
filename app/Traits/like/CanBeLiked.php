<?php

/*
 * This file is part of the overtrue/laravel-like.
 *
 * (c) overtrue <anzhengchao@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace App\Traits\like;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait CanBeLiked.
 */
trait CanBeLiked
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $user
     *
     * @return bool
     */
    public function isLikedBy(Model $user)
    {
        if (\is_a($user, config('auth.providers.users.model'))) {
            if ($this->relationLoaded('likers')) {
                return $this->likers->where($user->getKeyName(), $user->getKey())->first();
            }

            return tap($this->relationLoaded('likers') ? $this->likers : $this->likers())
                    ->where($user->getTable().'.'.$user->getKeyName(), $user->getKey())->first();
        }

        return false;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function likes()
    {
        return $this->morphMany(config('like.like_model'), 'likable');
    }

    /**
     * Return followers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function likers()
    {
        return $this->belongsToMany(config('auth.providers.users.model'), config('like.likes_table'), 'likable_id', config('like.user_foreign_key'))
            ->withPivot(['likable_id', 'user_id', 'likable_state'])
            ->where('likable_type', static::class);
    }
}
