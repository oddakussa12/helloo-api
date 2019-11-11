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
            //'post_id' => $this->post_id,
            'user_id' => $this->user_id,
            'post_uuid' => $this->post_uuid,
            'post_default_locale' => $this->post_default_locale,
            'post_content_default_locale' => $this->post_content_default_locale,
            'post_title' => $this->post_decode_title,
            'post_media'=>$this->post_media,
            'post_type' => $this->post_type,
//            'post_rate' => $this->fire_rate,
            'post_like_num' => $this->post_like_num,
            'post_comment_num' => $this->post_comment_num,
            //'translations' => PostTranslationCollection::collection($this->translations),
//            'post_like_state'=>$this->post_like_state,
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
                    'user_follow_state'=>auth()->check()&&auth()->id()!=$this->user_id?auth()->user()->isFollowing($this->user_id):false,

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
            $this->mergeWhen(!$request->routeIs('post.hot'), function (){
                return collect([
                    'post_owner' => auth()->check()?$this->ownedBy(auth()->user()):false,
                    'user_name'=>$this->owner->user_name,
                    'user_avatar'=>$this->owner->user_avatar,
                    'user_country'=>$this->owner->user_country,
                    'user_is_guest' => $this->user_is_guest,
                ]);
            }),
        ];
    }

    private function country()
    {
        return DB::table('posts_comments')
            ->select(DB::raw('count(*) as country_num') , 'countries.country_code as country_code' , 'countries.country_id as country_id')
            ->leftJoin('countries', 'posts_comments.comment_country_id', '=', 'countries.country_id')
            ->where('post_id' , $this->post_id)
            ->groupBy('posts_comments.comment_country_id')
            ->orderBy('country_num' , 'desc')
            ->paginate(7);
    }

}