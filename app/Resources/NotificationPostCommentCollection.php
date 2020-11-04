<?php


namespace App\Resources;

use Illuminate\Http\Resources\Json\Resource;

class NotificationPostCommentCollection extends Resource
{
    /**
     *
     */
    public function toArray($request)
    {
        return [
            'comment_id' => $this->comment_id,
            'comment_comment_p_id' => $this->comment_comment_p_id,
            'comment_top_id' => $this->comment_top_id,
            'comment_content' => $this->comment_decode_content,
            'comment_default_locale' => $this->comment_default_locale,
            'comment_default_content' => $this->comment_default_content,
            'comment_image' => $this->comment_image,
            'comment_created_at' => optional($this->comment_created_at)->toDateTimeString(),
            'comment_format_created_at' => $this->comment_format_created_at,
            'owner'=>$this->when(isset($this->owner) , function () use ($request){
                return new UserCollection($this->owner);
            }),
            'post'=>$this->when(isset($this->post), function (){
                return new PostCollection($this->post);
            }),
            'parent'=>$this->when(!empty($this->parentComment), function (){
                return new NotificationPostCommentParentCollection($this->parentComment);
            }),
        ];
    }
}