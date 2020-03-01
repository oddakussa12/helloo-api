<?php


namespace App\Resources;

use App\Traits\CachablePost;
use Illuminate\Http\Resources\Json\Resource;

class PostCollection extends Resource
{
    use CachablePost;
    /**
     *
     */
    public function toArray($request)
    {
        return [
            'post_uuid' => $this->post_uuid,
            'post_default_locale' => $this->post_default_locale,
            'post_content_default_locale' => $this->post_content_default_locale,
            'post_title' => $this->when(true , function() use ($request){
                if($request->routeIs('notification.index')||$request->routeIs('post.hot'))
                {
                    return $this->post_index_title;
                }
                return $this->post_decode_title;
            }),
            'post_media'=>$this->post_media,
            'post_type' => $this->post_type,
            'post_comment_num' => $this->post_comment_num,
            'post_view_num' => $this->viewVirtualCount($this->post_id),
            'post_created_at'=>optional($this->post_created_at)->toDateTimeString(),
            'post_format_created_at'=> $this->post_format_created_at,
            'tags'=>$this->when($this->relationLoaded('tag') , function (){
                return TagCollection::collection($this->tags);
            }),
            $this->mergeWhen($request->routeIs('post.show'), function (){
                return collect([
                    'post_default_title' => $this->post_default_title,
                    'post_default_content' => $this->post_default_content,
                    'post_content' => $this->post_decode_content,
                ]);
            }),
//            'post_country'=>$this->when($request->routeIs('post.myself') , function (){
//                return collect([
//                    'total'=>collect($this->countryNum)->get('country_num' , 0),
//                    'data'=>$this->countries
//                ]);
//            }),
            'post_country'=>$this->countryCount($this->post_id),
            $this->mergeWhen($request->routeIs('post.show'), function (){
                return collect([
                    'post_media'=>$this->post_media,
//                    'post_view_num' => $this->post_view_num,
                ]);
            }),
            'post_owner' =>$this->when(!$request->routeIs('post.hot') , function (){
                return $this->post_owner;
            }),
            'postLike' =>$this->likeCount($this->post_id),
            'post_like_state'=>$this->post_like_state,
            'post_dislike_state'=>$this->post_dislike_state,
            $this->mergeWhen(!($request->routeIs('post.hot')||$request->routeIs('notification.index')), function (){
                return collect(array(
                    'owner'=>new UserCollection($this->owner),
                ));
            }),
            'post_event_country'=>$this->post_event_country
        ];
    }


}