<?php

namespace App\Listeners;

use App\Jobs\Jpush;
use App\Traits\CachablePost;
use App\Events\PostCommentCreated;

class PostCommentCreatedListener
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
        $commentNum = $post->post_comment_num+$type;
        $likeNum = $post->post_like_num;
        $createdTime = $post->post_created_at;
        $rate = rate_comment_v2($commentNum , $createdTime , $likeNum);
        if($rate!=$post->post_rate)
        {
            $extra = array('post_rate'=>$rate);
        }
        $post->increment('post_comment_num' , $type , $extra);
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
        $this->updateCountry($post->post_id , $user->user_country_id);
        $user->increment('user_score' , 3);
    }
}
