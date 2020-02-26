<?php

namespace App\Listeners;

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
     * @param Liked $event
     * @return void
     */
    public function handle(Liked $event)
    {
        //获取事件中保存的信息
        $extra = array();
        $object = $event->getObject();
        $user = $event->getUser();
        if($object instanceof Post)
        {
            $keyName = $object->getKeyName();
            $tmpLikeNum = $event->getTmpLikeNum();
            $keyValue = $object->getKey();
            $commenterNum = $this->commenterCount($keyValue);
            $countryNum = $this->countryNum($keyValue);
            $commentNum = $object->post_comment_num;
            $likeNum = $object->post_like_num+$event->getType();
            $createdTime = $object->post_created_at;
            $rate = rate_comment_v3($commentNum , $createdTime , $likeNum , $commenterNum , $countryNum);
            if($rate!=$object->post_rate)
            {
                $extra = array('post_rate'=>$rate);
            }
            $object->increment('post_like_num' , $event->getType() , $extra);
            notify('user.post_like' ,
                array(
                    'from'=>$user->user_id ,
                    'to'=>$object->user_id ,
                    'extra'=>array(
                        "{$keyName}"=>$keyValue,
                    ) ,
                    'setField'=>array('contact_id' , $keyValue),
                    'url'=>'/notification/post/'.$keyValue,
                ),
                false
            );
            $this->updateLikeCount($keyValue , 'like' , $tmpLikeNum);
            $this->updateCountry($keyValue , $user->user_country_id);
        }else if($object instanceof PostComment)
        {
            $extra = array();
            $keyName = $object->getKeyName();
            $post = $object->post;
            $keyValue = $object->getKey();
            $comment_like_temp_num = request()->input('comment_like_temp_num' , 0);
            if($comment_like_temp_num >0){
                $extra = array('comment_like_temp_num'=>\DB::raw('comment_like_temp_num+'.$comment_like_temp_num));
            }
            $object->increment('comment_like_num' , $event->getType() , $extra);
            notify('user.like' ,
                array(
                    'from'=>$user->user_id ,
                    'to'=>$object->user_id ,
                    'extra'=>array(
                        $keyName=>$keyValue,
                        'post_id'=>$post->post_id,
                    ) ,
                    'setField'=>array('contact_id' , $keyValue),
                    'url'=>'/notification/post/'.$post->post_id.'/postComment/'.$keyValue,
                )
            );

        }
        $user->increment('user_score');
    }
}
