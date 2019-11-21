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
            'comment_like_num' => $this->comment_like_num,
            'comment_content' => $this->comment_decode_content,
            'comment_default_locale' => $this->comment_default_locale,
            'comment_default_content' => $this->comment_default_content,
            'comment_image' => $this->comment_image,
            'comment_created_at' => optional($this->comment_created_at)->toDateTimeString(),
            'comment_format_created_at' => $this->comment_format_created_at,
            'comment_like_state'=>$this->comment_like_state,
            'owner'=>collect(array(
                'user_id'=>$this->owner->user_id,
                'user_name'=>$this->owner->user_name,
                'user_avatar'=>$this->owner->user_avatar,
                'user_country'=>$this->owner->user_country,
                'user_is_guest'=>$this->owner->user_is_guest,
            )),
            $this->mergeWhen($request->routeIs('notification.index'), function (){
                return collect([
                    'to'=>collect(array(
                        'user_id'=>$this->to->user_id,
                        'user_name'=>$this->to->user_name,
                        'user_avatar'=>$this->to->user_avatar,
                        'user_country'=>$this->to->user_country,
                        'user_is_guest'=>$this->to->user_is_guest,
                    )),
                ]);
            }),
            'children' => $this->when($request->routeIs('show.comment.by.post') , function (){
                $this->children->each(function($item , $key){
                    $item->post_uuid = $this->post_uuid;
                });
                return $this->collection($this->children);
            }),
            'comment_owner' => auth()->check()?$this->ownedBy(auth()->user()):false,
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
            })
        ];
    }
}