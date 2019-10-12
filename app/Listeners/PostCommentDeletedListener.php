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
        $post->decrement('post_comment_num');
        if($object->comment_comment_p_id===0)
        {
            notify_remove([5] , $post);
        }else{
            notify_remove([6] , $object->parent);
        }
    }
}
