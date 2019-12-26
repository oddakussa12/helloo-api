<?php

/*
 * This file is part of the overtrue/laravel-like.
 *
 * (c) overtrue <anzhengchao@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace App\Traits\dislike;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait CanBeLiked.
 */
trait CanBeDisliked
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $user
     *
     * @return bool
     */
    public function isDislikedBy(Model $user)
    {
        if (\is_a($user, config('auth.providers.users.model'))) {
            if ($this->relationLoaded('dislikers')) {
                return $this->dislikers->where($user->getKeyName(), $user->getKey())->first();
            }

            return tap($this->relationLoaded('dislikes') ? $this->dislikes : $this->dislikes())
                    ->where($user->getTable().'.'.$user->getKeyName(), $user->getKey())->first();
        }

        return false;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function dislikes()
    {
        return $this->morphMany(config('dislike.dislike_model'), 'dislikable');
    }

    /**
     * Return followers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function dislikers()
    {
        return $this->belongsToMany(config('auth.providers.users.model'), config('dislike.dislikes_table'), 'dislikable_id', config('dislike.user_foreign_key'))
            ->withPivot(['dislikable_id', 'user_id'])
            ->where('dislikable_type', static::class);
    }
}
