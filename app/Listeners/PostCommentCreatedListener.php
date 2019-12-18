<?php

namespace App\Listeners;

use App\Jobs\Jpush;
use App\Events\PostCommentCreated;

class PostCommentCreatedListener
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
    public function handle(PostCommentCreated $event)
    {
        //获取事件中保存的信息
        $object = $event->getObject();
        $post = $object->post;
        $post->increment('post_comment_num');
        $rate = rate_comment_v2($post->post_comment_num , $post->post_created_at);
        if($rate!=$post->post_rate)
        {
            $post->post_rate = $rate;
            $post->save();
        }
        if($object->comment_comment_p_id===0)
        {
            Jpush::dispatch('comment' , auth()->user()->user_name , $post->user_id)->onQueue('op_jpush');
            $owner = $post->owner;
            notify('user.post_comment' ,
                array(
                    'from'=>auth()->id() ,
                    'to'=>$owner->user_id ,
                    'extra'=>array(
                        'comment_id'=>$object->{$object->getKeyName()},
                        'post_id'=>$post->post_id,
                    ) ,
                    'setField'=>array('contact_id' , $post->post_id),
                    'url'=>'/notification/post/'.$post->post_id.'/postComment/'.$object->{$object->getKeyName()},
                )
            );
        }else{
            $owner = $object->parent->owner;
            Jpush::dispatch('comment' , auth()->user()->user_name , $object->parent->user_id)->onQueue('op_jpush');
            notify('user.comment' ,
                array(
                    'from'=>auth()->id() ,
                    'to'=>$owner->user_id ,
                    'extra'=>array(
                        'comment_id'=>$object->{$object->getKeyName()},
                        'post_id'=>$post->post_id,
                        'comment_comment_p_id'=>$object->comment_comment_p_id
                    ) ,
                    'setField'=>array('contact_id' , $object->parent->{$object->parent->getKeyName()}),
                    'url'=>'/notification/post/'.$post->post_id.'/postComment/'.$object->{$object->getKeyName()},
                )
            );
        }
        if(auth()->user()->user_last_name!='test!@#qaz')
        {
            notify('admin.comment_notice' ,
                array(
                    'to'=>2 ,
                    'extra'=>array(
                        'comment_id'=>$object->{$object->getKeyName()},
                        'comment_comment_p_id'=>$object->comment_comment_p_id,
                        'post_id'=>$post->post_id,
                        'from_id'=>auth()->id() ,
                        'from_name'=>auth()->user()->user_name ,
                        'to_id'=>$owner->user_id ,
                        'to_name'=>$owner->user_name ,
                    ) ,
                    'url'=>'/notification/post/'.$object->post_id.'/postComment/'.$object->{$object->getKeyName()},
                ),
                true
            );
        }

        $user = auth()->user();
        $user->increment('user_score' , 3);


    }
}
