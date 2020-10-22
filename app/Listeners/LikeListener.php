<?php

namespace App\Listeners;

use App\Models\Post;
use App\Events\Liked;
use App\Models\PostComment;
use App\Traits\CachablePost;
use App\Traits\CachableUser;

class LikeListener
{
    use CachablePost,CachableUser;
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
//        $user->increment('user_score');
//        $this->updateUserScoreRank($user->user_id);
        $isAuth = $user->user_id==$object->user_id;

        if($object instanceof Post)
        {
            $keyName      = $object->getKeyName();
            $tmpLikeNum   = tempPostLikeNum($object->post_like_num);
            $keyValue     = $object->getKey();
            $commenterNum = $this->commenterCount($keyValue);
            $countryNum   = $this->countryNum($keyValue);
            $commentNum   = $object->post_comment_num;
            $likeNum      = $object->post_like_num+$event->getType();
            $createdTime  = $object->post_created_at;
            $rate         = rate_comment_v3($commentNum , $createdTime , $likeNum , $commenterNum , $countryNum);
            if($rate!=$object->post_rate) {
                $extra = array('post_rate'=>$rate);
                $this->updateTopicPostRate($keyValue , $rate);
            }
            $object->increment('post_like_num' , $event->getType() , $extra);
            $this->updateLikeCount($keyValue , 'like' , $tmpLikeNum , $isAuth);
            $this->updateCountry($keyValue , $user->user_country_id);
            $this->updateUserPostLikeCount($user->user_id);
            !$isAuth && notify('user.post_like',
                [
                    'from'     => $user->user_id ,
                    'to'       => $object->user_id ,
                    'extra'    => [$keyName => $keyValue, 'value' => $object->post_uuid ?? ''],
                    'setField' => array('contact_id' , $keyValue),
                    'url'      => '/notification/post/'.$keyValue,
                ]
            );
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
            $this->updateUserPostCommentLikeCount($user->user_id);
            !$isAuth && notify('user.like',
                [
                    'from' => $user->user_id ,
                    'to'   => $object->user_id ,
                    'extra'=> [
                        $keyName => $keyValue,
                        'post_id'=> $post->post_id,
                        'value'  => $post->post_uuid,
                    ],
                    'setField' =>['contact_id', $keyValue],
                    'url'=>'/notification/post/'.$post->post_id.'/postComment/'.$keyValue,
                ]
            );

        }

    }
}
