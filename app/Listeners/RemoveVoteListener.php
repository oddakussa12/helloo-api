<?php

namespace App\Listeners;

use App\Models\Like;
use App\Models\Post;
use App\Models\Dislike;
use App\Events\RemoveVote;
use App\Models\PostComment;
use App\Traits\CachablePost;
use App\Traits\CachableUser;

class RemoveVoteListener
{
    use CachablePost,CachableUser;
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
        $extra = array();
        $type = $event->getType();
        $object = $event->getObject();
        $relation = $event->getRelation();
        $user = $event->getUser();
        if($object instanceof Post)
        {
            $keyValue = $object->getKey();
            $commenterNum = $this->commenterCount($keyValue);
            $countryNum = $this->countryNum($keyValue);
            $commentNum = $object->post_comment_num;
            $likeNum = $object->post_like_num-$type;
            $createdTime = $object->post_created_at;
            $rate = rate_comment_v3($commentNum , $createdTime , $likeNum , $commenterNum , $countryNum);
            if($rate!=$object->post_rate)
            {
                $extra = array('post_rate'=>$rate);
            }
            $object->decrement('post_like_num' , $type , $extra);

            if($relation instanceof Like)
            {
                notify_remove([9] , $object , $user);
                $this->updateLikeCount($keyValue , 'revokeLike');
            }elseif ($relation instanceof Dislike)
            {
                notify_remove([10] , $object , $user);
                $this->updateLikeCount($keyValue , 'revokeDislike');
            }
            $this->updateCountry($keyValue , $user->user_country_id , false);
            $this->updateUserPostLikeCount($user->getKey() , -1);
        }else if($object instanceof PostComment)
        {
            $object->decrement('comment_like_num' , $event->getType());
            if($relation instanceof Like)
            {
                notify_remove([3] , $object , $user);
            }
            $this->updateUserPostCommentLikeCount($user->getKey() , -1);
        }
        if($relation->created_at>config('common.score_date'))
        {
            $user->decrement('user_score');
            $this->updateUserScoreRank($user->user_id , -1);
        }
    }

}
