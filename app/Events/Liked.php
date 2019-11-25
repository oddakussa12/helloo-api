<?php

/*
 * This file is part of the overtrue/laravel-like.
 *
 * (c) overtrue <anzhengchao@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace App\Events;

use App\Models\Post;
use App\Models\PostComment;
use App\Resources\PostCollection;
use App\Resources\PostCommentCollection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
class Liked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $user;

    private $object;
    /**
     * @var int
     */
    private $type;


    public function __construct($user , $object , $type=1)
    {
        $this->user = $user;
        $this->object = $object;
        $this->type = $type;
//        $this->dontBroadcastToCurrentUser();
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getObject()
    {
        return $this->object;
    }

    public function getType()
    {
        return $this->type;
    }


//    public function broadcastOn()
//    {
//        return new PrivateChannel('channel-name');
//    }

//    public function broadcastOn()
//    {
//        return new PrivateChannel('App.Models.User.'.$this->object->user_id);
//        //return [(new \ReflectionClass($this->object))->getShortName().'.'.optional($this->object)->{$this->object->getKeyName()}];
//    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        $object = $this->object;
        if($object instanceof Post)
        {
            $data = array(
                'url'    => '/post/'.$object->{$object->getKeyName()},
                'name'   => $object->owner->user_name,
                'avatar' => $object->owner->user_avatar,
                //'body'   => '@'.$object->owner->user_name.' liked to your post on "'.$object->post_title.'"',
                'body'   => array(
                    'who'=>$object->owner->user_name,
                    'object'=>$object->post_title,
                ),
            );
        }elseif($object instanceof PostComment)
        {
            $data = array(
                'type'   => 'commentLiked',
                'url'    => '/postComment/'.$object->{$object->getKeyName()},
                'name'   => $object->owner->user_name,
                'avatar' => $object->owner->user_avatar,
                'body'   => '@'.$object->owner->user_name.trans('notification.commentLike.one').' ['.$object->post->post_title.'].',
                'trans'=>'notification.commentLike.one'
            );
        }else{
            $data = array();
        }

        return [
            'data' => $data,
        ];

//        if($this->object instanceof Post)
//        {
//            $data = (new PostCollection($this->object))->resolve();
//        }else if($this->object instanceof PostComment)
//        {
//            $data = (new PostCommentCollection($this->object))->resolve();
//        }else{
//            $data = [];
//        }
//        return [
//            'data' => $data,
//        ];
    }

//    public function broadcastAs()
//    {
//        return 'comment-like';
//    }
}
