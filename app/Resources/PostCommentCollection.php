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
            'comment_id' => $this->comment_id,
            'comment_comment_p_id' => $this->comment_comment_p_id,
            'comment_top_id' => $this->comment_top_id,
            'comment_like_num' => $this->comment_like_num,
            'comment_like_temp_num' => $this->comment_like_temp_num,
            'comment_content' => $this->comment_decode_content,
            'comment_default_locale' => $this->comment_default_locale,
            'comment_default_content' => $this->comment_default_content,
            'comment_image' => $this->comment_image,
            'comment_created_at' => optional($this->comment_created_at)->toDateTimeString(),
            'comment_format_created_at' => $this->comment_format_created_at,
            'comment_like_state'=>$this->comment_like_state,
            'owner'=>new UserCollection($this->owner),
            'children' => $this->when($request->routeIs('show.comment.by.post')&&isset($this->topTwoComments) , function (){
                return PostCommentCollection::collection($this->topTwoComments)->sortBy('comment_id')->values()->all();
            }),
            'comment_owner' => $this->comment_owner,
            'post_uuid'=>$this->when(!($request->routeIs('post.index')||$request->routeIs('post.top')), function (){
                if(isset($this->post_uuid))
                {
                    return $this->post_uuid;
                }
                $post = $this->post;
                if(!empty($post))
                {
                    return $post->post_uuid;
                }
                return '';
            }),
            'post'=>$this->when($this->relationLoaded('post'), function (){
                return new PostCollection($this->post);
            }),
            $this->mergeWhen($this->relationLoaded('parent'), function (){
                return collect(array(
                    'parent'=>new PostCommentSubCollection($this->parent),
                ));
            }),
            $this->mergeWhen($this->relationLoaded('to'), function (){
                return collect(array(
                    'to'=>new UserCollection($this->to),
                ));
            }),
            'subCommentsCount'=>$this->when(isset($this->subCommentsCount) , function (){
                return $this->subCommentsCount;
            })
        ];
    }
}