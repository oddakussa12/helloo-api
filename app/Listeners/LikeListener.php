<?php

namespace App\Listeners;

use App\Jobs\Jpush;
use App\Models\Post;
use App\Events\Liked;
use App\Models\PostComment;
use App\Traits\CachablePost;

class LikeListener
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
            $keyName = $object->getKeyName();
            $keyValue = $object->{$object->getKeyName()};
            $object->increment('post_like_num' , $event->getType());
            notify('user.post_like' ,
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
            $this->updateLikeCount($keyValue , 'like');
            $this->updateCountry($keyValue , auth()->user()->user_country_id);
        }else if($object instanceof PostComment)
        {
            Jpush::dispatch('like' , auth()->user()->user_name , $object->user_id)->onQueue('op_jpush');
            $extra = array();
            $keyName = $object->getKeyName();
            $keyValue = $object->{$object->getKeyName()};
            $comment_like_temp_num = request()->input('comment_like_temp_num' , 0);
            if($comment_like_temp_num >0){
                $extra = array('comment_like_temp_num'=>\DB::raw('comment_like_temp_num+'.$comment_like_temp_num));
            }
            $object->increment('comment_like_num' , $event->getType() , $extra);
            notify('user.like' ,
                array(
                    'from'=>auth()->id() ,
                    'to'=>$object->user_id ,
                    'extra'=>array(
                        $keyName=>$object->{$object->getKeyName()},
                        'post_id'=>$object->post_id,
                    ) ,
                    'setField'=>array('contact_id' , $keyValue),
                    'url'=>'/notification/post/'.$object->post_id.'/postComment/'.$keyValue,
                )
            );
            $this->updateCountry($keyValue , auth()->user()->user_country_id);
        }
        $user = auth()->user();
        $user->increment('user_score' , 1);
    }
}
