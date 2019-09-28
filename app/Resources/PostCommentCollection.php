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
            'post_id' => $this->post_id,
            'comment_id' => $this->comment_id,
            'comment_comment_p_id' => $this->comment_comment_p_id,
            'comment_like_num' => $this->comment_like_num,
            'comment_content' => $this->comment_decode_content,
            'comment_default_locale' => $this->comment_default_locale,
            'comment_default_content' => $this->comment_default_content,
            'comment_created_at' => optional($this->comment_created_at)->toDateTimeString(),
            //'translations' => PostCommentTranslationCollection::collection($this->translations),
            'comment_like_state'=>$this->comment_like_state,
            'user_name'=>$this->user->user_name,
            'user_id'=>$this->user->user_id,
            'user_avatar'=>$this->user->user_avatar,
            'user_country'=>$this->owner->user_country,
            'children' => $this->when($request->children==true , function(){
                return self::collection($this->children);
            }),
        ];
    }
}