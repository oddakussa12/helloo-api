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
use Illuminate\Support\Facades\Event;
use App\Events\Liked;
use App\Events\DisLiked;
use Overtrue\LaravelLike\Events\Unliked;

/**
 * Trait CanBeLiked.
 */
trait CanLike
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $object
     */
    public function like(Model $object)
    {
        $relation = $this->hasLiked($object);
        if (!$relation) {
            $like = app(config('like.like_model'));
            $like->{config('like.user_foreign_key')} = $this->getKey();
            $like->{config('like.likes_likable_state')}=1;
            $object->likes()->save($like);
            Event::dispatch(new Liked($this, $object));
        }else{
            if($relation->{config('like.likes_likable_state')}===-1)
            {
                $relation->{config('like.likes_likable_state')}=1;
                $relation->save();
                Event::dispatch(new Liked($this, $object , 2));
            }
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $object
     */
    public function unlike(Model $object)
    {
        $relation = $object->likes()
        ->where('likable_id', $object->getKey())
        ->where('likable_type', $object->getMorphClass())
        ->where(config('like.user_foreign_key'), $this->getKey())
        ->first();

        if ($relation) {
            if($relation->{config('like.likes_likable_state')}===1)
            {
                $relation->{config('like.likes_likable_state')}=-1;
                $relation->save();
                Event::dispatch(new DisLiked($this, $object , 2));
            }
        }else{
            $like = app(config('like.like_model'));
            $like->{config('like.user_foreign_key')} = $this->getKey();
            $like->{config('like.likes_likable_state')}=-1;
            $object->likes()->save($like);
            Event::dispatch(new DisLiked($this, $object));
        }
    }

    public function revoke(Model $object)
    {
        $relation = $object->likes()
            ->where('likable_id', $object->getKey())
            ->where('likable_type', $object->getMorphClass())
            ->where(config('like.user_foreign_key'), $this->getKey())
            ->first();
        if ($relation) {
            $relation->delete();
            if($relation->{config('like.likes_likable_state')}===1)
            {
                Event::dispatch(new DisLiked($this, $object , $relation));
            }else if($relation->{config('like.likes_likable_state')}===-1)
            {
                Event::dispatch(new Liked($this, $object , $relation));
            }
        }
    }
    /**
     * @param \Illuminate\Database\Eloquent\Model $object
     */
    public function toggleLike(Model $object)
    {
        $this->hasLiked($object) ? $this->unlike($object) : $this->like($object);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $object
     *
     * @return bool
     */
    public function hasLiked(Model $object)
    {
        return tap($this->relationLoaded('likes') ? $this->likes : $this->likes())
            ->where('likable_id', $object->getKey())
            ->where('likable_type', $object->getMorphClass())
            ->first();
    }

    /**
     * Return like.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function likes()
    {
        return $this->hasMany(config('like.like_model'), config('like.user_foreign_key'), $this->getKeyName());
    }

    /**
     * @param string|null $model
     *
     * @return mixed
     */
    public function likedItems(string $model = null)
    {
        $this->load(['likes' => function ($query) use ($model) {
            $model && $query->where('likable_type', app($model)->getMorphClass());
        }, 'likes.likable']);

        return $this->likes->pluck('likable');
    }
}
