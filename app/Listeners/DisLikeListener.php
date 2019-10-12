<?php

namespace App\Listeners;

use App\Models\Post;
use App\Models\PostComment;
use App\Events\DisLiked;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class DisLikeListener
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
    public function handle(DisLiked $event)
    {
        //获取事件中保存的信息
        $object = $event->getObject();
        $object->refresh();
        if($object instanceof Post)
        {
            $object->decrement('post_like_num' , $event->getType());
        }else if($object instanceof PostComment)
        {
            $object->decrement('comment_like_num' , $event->getType());
            notify_remove([3] , $object);
        }
    }

}
