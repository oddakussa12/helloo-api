<?php

namespace App\Listeners;

use App\Models\Post;
use App\Models\PostComment;
use App\Events\Liked;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;

class LikeListener
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

    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(Liked $event)
    {
        //获取事件中保存的信息
        $object = $event->getObject();
        $object->refresh();
        if($object instanceof Post)
        {
            $object->increment('post_like_num' , $event->getType());
        }else if($object instanceof PostComment)
        {
            $object->increment('comment_like_num' , $event->getType());
            $object->increment('comment_like_temp_num' , request()->input('comment_like_temp_num' , 0));
            notify('user.like' ,
                array(
                    'from'=>auth()->id() ,
                    'to'=>$object->owner->user_id ,
                    'extra'=>array(
                        'comment_id'=>$object->{$object->getKeyName()},
                        'post_id'=>$object->post_id,
                    ) ,
                    'setField'=>array('contact_id' , $object->{$object->getKeyName()}),
                    'url'=>'/notification/post/'.$object->post_id.'/postComment/'.$object->{$object->getKeyName()},
                )
            );
            if(auth()->user()->user_last_name!='test!@#qaz')
            {
                notify('admin.like_notice' ,
                    array(
                        'to'=>2 ,
                        'extra'=>array(
                            'comment_id'=>$object->{$object->getKeyName()},
                            'post_id'=>$object->post_id,
                            'from_id'=>auth()->id() ,
                            'from_name'=>auth()->user()->user_name ,
                            'to_id'=>$object->owner->user_id ,
                            'to_name'=>$object->owner->user_name ,
                        ) ,
                        'url'=>'/notification/post/'.$object->post_id.'/postComment/'.$object->{$object->getKeyName()},
                    ),
                    true
                );
            }
        }
        $user = auth()->user();
        $user->increment('user_score' , 2);
    }
}
