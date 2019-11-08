<?php


namespace App\Resources;

use App\Repositories\Contracts\UserRepository;
use Illuminate\Http\Resources\Json\Resource;

class PostCommentCollection extends Resource
{
    /**
     *
     */
    public function toArray($request)
    {
        return [
//            'post_id' => $this->post_id,
            'comment_id' => $this->comment_id,
            'comment_comment_p_id' => $this->comment_comment_p_id,
            'comment_like_num' => $this->comment_like_num,
            'comment_content' => $this->comment_decode_content,
            'comment_default_locale' => $this->comment_default_locale,
            'comment_default_content' => $this->comment_default_content,
            'comment_image' => $this->comment_image,
            'comment_created_at' => optional($this->comment_created_at)->toDateTimeString(),
            'comment_format_created_at' => $this->comment_format_created_at,
            //'translations' => PostCommentTranslationCollection::collection($this->translations),
            'comment_like_state'=>$this->comment_like_state,

            'user_name'=>$this->owner->user_name,
            'user_id'=>$this->owner->user_id,
            'user_avatar'=>$this->owner->user_avatar,
            'user_country'=>$this->owner->user_country,

            'comment_owner' => auth()->check()?$this->ownedBy(auth()->user()):false,
            'children' => $this->when($request->children==true , function(){
                return self::collection($this->children);
            }),
            'post_uuid'=>$this->when(!($request->routeIs('post.index')||$request->routeIs('post.top'))&&!empty($this->post), function (){
                return $this->post->post_uuid;
            })
        ];
    }
}