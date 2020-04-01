<?php


namespace App\Resources;

use Illuminate\Http\Resources\Json\Resource;

class PostCommentSubCollection extends Resource
{
    /**
     *
     */
    public function toArray($request)
    {
        return [
            'comment_id' => $this->comment_id,
            'comment_content' => $this->comment_decode_content,
            'comment_image' => $this->comment_image,
            'comment_created_at' => optional($this->comment_created_at)->toDateTimeString(),
            'comment_format_created_at' => $this->comment_format_created_at,
            'owner'=>new UserCollection($this->owner),
            $this->mergeWhen($this->relationLoaded('to'), function (){
                return collect(array(
                    'to'=>new UserCollection($this->to),
                ));
            })
        ];
    }
}