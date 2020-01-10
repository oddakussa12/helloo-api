<?php

namespace App\Listeners;

use App\Traits\CachablePost;
use App\Events\PostCommentDeleted;

class PostCommentDeletedListener
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
     * @param PostCommentDeleted $event
     * @return void
     */
    public function handle(PostCommentDeleted $event)
    {
        //获取事件中保存的信息
        $type = 1;
        $extra = array();
        $object = $event->getObject();
        $post = $object->post;
        $user = $event->getUser();
        if(empty($post))
        {
            abort(404 , 'Post has been deleted');
        }
        if($object->comment_comment_p_id===0)
        {
            notify_remove([5] , $post , $user);
        }else{
            $parent = $object->parent;
            if(empty($parent))
            {
                abort(404 , 'Parent comment has been deleted');
            }
            notify_remove([6] , $parent , $user);
        }
        $commentNum = $post->post_comment_num-$type;
        $likeNum = $post->post_like_num;
        $createdTime = $post->post_created_at;
        $rate = rate_comment_v2($commentNum , $createdTime , $likeNum);
        if($rate!=$post->post_rate)
        {
            $extra = array('post_rate'=>$rate);
        }
        $post->decrement('post_comment_num' , $type , $extra);
        $this->updateCountry($post->post_id , $user->user_country_id , false);
        if($object->comment_created_at>config('common.score_date'))
        {
            $user->decrement('user_score' , 3);
        }
    }
}
