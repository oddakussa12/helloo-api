<?php

namespace App\Listeners;

use App\Models\Post;
use App\Events\DisLiked;
use App\Models\PostComment;
use App\Traits\CachablePost;

class DisLikeListener
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
            $keyName = $object->getKeyName();
            $keyValue = $object->{$object->getKeyName()};
            $object->increment('post_like_num' , $event->getType());
            notify('user.post_dislike' ,
                array(
                    'from'=>auth()->id() ,
                    'to'=>$object->user_id ,
                    'extra'=>array(
                        "{$keyName}"=>$keyValue,
                    ) ,
                    'setField'=>array('contact_id' , $keyValue),
                    'url'=>'/notification/post/'.$keyValue,
                )
            );
            $this->updateLikeCount($keyValue , 'dislike');
            $this->updateCountry($keyValue , auth()->user()->user_country_id);
        }else if($object instanceof PostComment)
        {
            $object->decrement('comment_like_num' , $event->getType());
            notify_remove([3] , $object);
        }
        $user = auth()->user();
        $user->decrement('user_score' , 1);

    }

}
