<?php

namespace App\Listeners;

use App\Models\Like;
use App\Models\Post;
use App\Models\Dislike;
use App\Events\RemoveVote;
use App\Models\PostComment;
use App\Traits\CachablePost;

class RemoveVoteListener
{
    use CachablePost;
    /**
     * 失败重试次数
     * @var int
     */
    public $tries = 1;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param RemoveVote $event
     * @return void
     */
    public function handle(RemoveVote $event)
    {
        //获取事件中保存的信息
        $object = $event->getObject();
        $relation = $event->getRelation();
        $object->refresh();
        if($object instanceof Post)
        {
            $keyName = $object->getKeyName();
            $keyValue = $object->{$object->getKeyName()};
            $object->decrement('post_like_num' , $event->getType());
            if($relation instanceof Like)
            {
                notify_remove([9] , $object);
                $this->updateLikeCount($keyValue , 'revokeLike');
            }elseif ($relation instanceof Dislike)
            {
                notify_remove([10] , $object);
                $this->updateLikeCount($keyValue , 'revokeDislike');
            }
            $this->updateCountry($keyValue , auth()->user()->user_country_id , false);
        }else if($object instanceof PostComment)
        {
            $object->decrement('comment_like_num' , $event->getType());
            if($relation instanceof Like)
            {
                notify_remove([3] , $object);
            }
        }

        if($relation->created_at>config('common.score_date'))
        {
            $user = auth()->user();
            $user->decrement('user_score' , 1);
        }
    }

}