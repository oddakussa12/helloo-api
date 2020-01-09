<?php

/*
 * This file is part of the overtrue/laravel-like.
 *
 * (c) overtrue <anzhengchao@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace App\Traits\dislike;

use App\Events\Liked;
use App\Events\DisLiked;
use App\Events\RemoveVote;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;
/**
 * Trait CanDislike.
 */
trait CanDislike
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $object
     */
    public function dislike(Model $object , $type=1)
    {
        $relation = $this->hasDisliked($object);
        if (!$relation) {
            $dislike = app(config('dislike.dislike_model'));
            $dislike->{config('dislike.user_foreign_key')} = $this->getKey();
            $dislike->{config('dislike.dislikes_country_field')} = $this->user_country_id;
            $object->dislikes()->save($dislike);
            Event::dispatch(new DisLiked($this, $object , $relation , $type));
            return $this->user_country_id;
        }
        return false;
    }



    public function revokeDislike(Model $object)
    {
        $relation = $object->dislikes()
            ->where('dislikable_id', $object->getKey())
            ->where('dislikable_type', $object->getMorphClass())
            ->where(config('dislike.user_foreign_key'), $this->getKey())
            ->first();
        if ($relation) {
            $relation->delete();
            Event::dispatch(new RemoveVote($this, $object , $relation));
            return $relation->dislikable_country;
        }
        return false;
    }


    /**
     * @param \Illuminate\Database\Eloquent\Model $object
     *
     * @return bool
     */
    public function hasDisliked(Model $object)
    {
        return tap($this->relationLoaded('dislikes') ? $this->dislikes : $this->dislikes())
            ->where('dislikable_id', $object->getKey())
            ->where('dislikable_type', $object->getMorphClass())
            ->first();
    }

    /**
     * Return like.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function dislikes()
    {
        return $this->hasMany(config('dislike.dislike_model'), config('dislike.user_foreign_key'), $this->getKeyName());
    }

}
