<?php

namespace App\Listeners;

use App\Traits\CachablePost;
use App\Traits\CachableUser;
use App\Events\PostCommentCreated;

class PostCommentCreatedListener
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
     * @param PostCommentCreated $event
     * @return void
     */
    public function handle(PostCommentCreated $event)
    {
        //获取事件中保存的信息
        $type = 1;
        $extra = array();
        $postComment = $event->getPostComment();
        $post = $event->getPost();
        $user = $event->getUser();
        $keyValue = $post->getKey();
        $commenterNum = $this->commenterCount($keyValue);
        $countryNum = $this->countryNum($keyValue);
        $commentNum = $post->post_comment_num+$type;
        $likeNum = $post->post_like_num;
        $createdTime = $post->post_created_at;
        $rate = rate_comment_v3($commentNum , $createdTime , $likeNum , $commenterNum , $countryNum);
        if($rate!=$post->post_rate)
        {
            $extra = array('post_rate'=>$rate);
            $this->updateTopicPostRate($keyValue , $rate);
        }
        $post->increment('post_comment_num' , $type , $extra);
//        $user->increment('user_score' , 3);
        $this->updateUserPostCommentCount($user->user_id);
        $this->updateCountry($post->post_id , $user->user_country_id);
        $this->updateCommenter($post->post_id , $user->getKey());
        $this->updateCommentCount($post->post_id , $user->getKey());
//        $this->updateUserScoreRank($user->user_id , 3);

        if($postComment->comment_comment_p_id===0)
        {
            notify('user.post_comment' ,
                array(
                    'from'=>$user->user_id ,
                    'to'=>$post->user_id ,
                    'extra'=>array(
                        'comment_id'=>$postComment->getKey(),
                        'post_id'=>$post->post_id,
                    ) ,
                    'setField'=>array('contact_id' , $post->post_id),
                    'url'=>'/notification/post/'.$post->post_id.'/postComment/'.$postComment->getKey(),
                )
            );
        }else{
            $parent = $postComment->parent;
            notify('user.comment' ,
                array(
                    'from'=>$user->user_id ,
                    'to'=>$parent->user_id ,
                    'extra'=>array(
                        'comment_id'=>$postComment->getKey(),
                        'post_id'=>$post->post_id,
                        'comment_comment_p_id'=>$postComment->comment_comment_p_id
                    ) ,
                    'setField'=>array('contact_id' , $parent->getKey()),
                    'url'=>'/notification/post/'.$post->post_id.'/postComment/'.$postComment->getKey(),
                )
            );
        }

    }
}
