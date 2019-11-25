<?php


namespace App\Resources;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Resources\Json\Resource;

class PostCollection extends Resource
{
    /**
     *
     */
    public function toArray($request)
    {
        return [
            'post_uuid' => $this->post_uuid,
            'post_default_locale' => $this->post_default_locale,
            'post_content_default_locale' => $this->post_content_default_locale,
            'post_title' => $this->post_decode_title,
            'post_media'=>$this->post_media,
            'post_type' => $this->post_type,

            'post_like_num' => $this->post_like_num,
            'post_comment_num' => $this->post_comment_num,

            'post_created_at'=>optional($this->post_created_at)->toDateTimeString(),
            'post_format_created_at'=> $this->post_format_created_at,
            'tags'=>$this->when($request->has('tag') , function (){
                return TagCollection::collection($this->tags);
            }),
            $this->mergeWhen($request->routeIs('post.show'), function (){
                return collect([
                    'post_default_title' => $this->post_default_title,
                    'post_default_content' => $this->post_default_content,
                    'post_content' => $this->post_decode_content,

                ]);
            }),
            'post_country'=>$this->when(!($request->routeIs('post.hot')||$request->routeIs('notification.index')) , function (){
                return collect([
                    'total'=>collect($this->countryNum)->get('country_num' , 0),
                    'data'=>$this->countries
                ]);
            }),
            $this->mergeWhen($request->routeIs('post.show'), function (){
                return collect([
                    'post_media'=>$this->post_media,
                    'post_view_num' => $this->post_view_num,
                ]);
            }),
            $this->mergeWhen(!$request->routeIs('post.hot') , ['post_owner' => auth()->check()?$this->ownedBy(auth()->user()):false]),
            $this->mergeWhen(!($request->routeIs('post.hot')||$request->routeIs('notification.index')), function (){
                return collect(array(
                    'owner'=>new UserCollection($this->owner),
                ));
            }),
        ];
    }


}