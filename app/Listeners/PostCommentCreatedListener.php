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
        $extra = array();
        $object = $event->getObject();
        $post = $object->post;
        $rate = rate_comment_v2($post->post_comment_num , $post->post_created_at);
        if($rate!=$post->post_rate)
        {
            $extra = array('post_rate'=>$rate);
        }
        $post->increment('post_comment_num' , 1 , $extra);
        if($object->comment_comment_p_id===0)
        {
            Jpush::dispatch('comment' , auth()->user()->user_name , $post->user_id)->onQueue('op_jpush');
            notify('user.post_comment' ,
                array(
                    'from'=>auth()->id() ,
                    'to'=>$post->user_id ,
                    'extra'=>array(
                        'comment_id'=>$object->{$object->getKeyName()},
                        'post_id'=>$post->post_id,
                    ) ,
                    'setField'=>array('contact_id' , $post->post_id),
                    'url'=>'/notification/post/'.$post->post_id.'/postComment/'.$object->{$object->getKeyName()},
                )
            );
        }else{
            $parent = $object->parent;
            Jpush::dispatch('comment' , auth()->user()->user_name , $object->parent->user_id)->onQueue('op_jpush');
            notify('user.comment' ,
                array(
                    'from'=>auth()->id() ,
                    'to'=>$parent->user_id ,
                    'extra'=>array(
                        'comment_id'=>$object->{$object->getKeyName()},
                        'post_id'=>$post->post_id,
                        'comment_comment_p_id'=>$object->comment_comment_p_id
                    ) ,
                    'setField'=>array('contact_id' , $parent->{$parent->getKeyName()}),
                    'url'=>'/notification/post/'.$post->post_id.'/postComment/'.$object->{$object->getKeyName()},
                )
            );
        }
        $user = auth()->user();
        $this->updateCountry($post->post_id , $user->user_country_id);
        $user->increment('user_score' , 3);
    }
}
