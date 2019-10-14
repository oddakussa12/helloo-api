<?php

namespace App\Listeners;

use App\Events\PostCommentDeleted;

class PostCommentDeletedListener
{
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
     * @param  object  $event
     * @return void
     */
    public function handle(PostCommentDeleted $event)
    {
        //获取事件中保存的信息
        $object = $event->getObject();
        $post = $object->post;
        if(empty($post))
        {
            abort(404 , 'Post has been deleted');
        }
        if($object->comment_comment_p_id===0)
        {
            $post->decrement('post_comment_num');
            notify_remove([5] , $post);
        }else{
            if(empty($object->parent))
            {
                abort(404 , 'Parent comment has been deleted');
            }
            $post->decrement('post_comment_num');
            notify_remove([6] , $object->parent);
        }
    }
}
