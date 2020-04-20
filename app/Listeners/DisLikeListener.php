<?php

namespace App\Listeners;

use App\Models\Post;
use App\Events\DisLiked;
use App\Models\PostComment;
use App\Traits\CachablePost;
use App\Traits\CachableUser;

class DisLikeListener
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
     * @param DisLiked $event
     * @return void
     */
    public function handle(DisLiked $event)
    {
        //获取事件中保存的信息
        $extra = array();
        $object = $event->getObject();
        $object->refresh();
        $user = $event->getUser();
        if($object instanceof Post)
        {
            $keyName = $object->getKeyName();
            $tmpDislikeNum = $event->getTmpDislikeNum();
            $keyValue = $object->getKey();
            $commenterNum = $this->commenterCount($keyValue);
            $countryNum = $this->countryNum($keyValue);
            $commentNum = $object->post_comment_num;
            $likeNum = $object->post_like_num+$event->getType();
            $createdTime = $object->post_created_at;
            $rate = rate_comment_v3($commentNum , $createdTime , $likeNum , $commenterNum , $countryNum);
            if($rate!=$object->post_rate)
            {
                $extra = array('post_rate'=>$rate);
            }
            $object->increment('post_like_num' , $event->getType() , $extra);
            notify('user.post_dislike' ,
                array(
                    'from'=>$user->user_id ,
                    'to'=>$object->user_id ,
                    'extra'=>array(
                        "{$keyName}"=>$keyValue,
                    ) ,
                    'setField'=>array('contact_id' , $keyValue),
                    'url'=>'/notification/post/'.$keyValue,
                ),
                false
            );
            $this->updateLikeCount($keyValue , 'dislike' , $tmpDislikeNum);
            $this->updateCountry($keyValue , $user->user_country_id);
            $this->updateUserPostDislikeCount($user->user_id);
        }else if($object instanceof PostComment)
        {
//            $object->decrement('comment_like_num' , $event->getType());
//            notify_remove([3] , $object , $user);
        }
        $user->increment('user_score');
        $this->updateUserScoreRank($user->user_id);

    }

}
