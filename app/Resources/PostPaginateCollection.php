<?php


namespace App\Resources;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Resources\Json\Resource;
use App\Repositories\Contracts\PostCommentRepository;

class PostPaginateCollection extends Resource
{
    /**
     *
     */
    public function toArray($request)
    {
        return [
            'post_id' => $this->post_id,
            'user_id' => $this->user_id,
            'post_uuid' => $this->post_uuid,
            'post_media' => $this->post_media,
            'post_default_locale' => $this->post_default_locale,
            'post_title' => $this->post_decode_title,
            //'post_content' => strip_tags($this->post_decode_content),
            'post_type' => $this->post_type,
            'post_like_num' => $this->post_like_num,
            'post_comment_num' => $this->post_comment_num,
            'post_rate' => $this->when(!$request->has('keywords') , function(){
                return $this->fire_rate;
            }),
//            'translations' => PostTranslationCollection::collection($this->translations),
//            'post_like_state'=>$this->post_like_state,
            'topTwoComments'=> $this->when($request->get('home')==true||$request->routeIs('post.top') , function (){
                return PostCommentCollection::collection($this->topTwoComments)->values()->all();
            }),
            'post_country'=> collect([
                'total'=>collect($this->countryNum)->get('country_num' , 0),
                'data'=>$this->countries
            ]),
            'post_created_at'=> optional($this->post_created_at)->toDateTimeString(),
            'post_format_created_at'=> $this->post_format_created_at,

            'user_name'=>$this->owner->user_name,
            'user_avatar'=>$this->owner->user_avatar,
            'user_country'=>$this->owner->user_country,
            'user_is_guest' => $this->owner->user_is_guest,

            'post_owner' => auth()->check()?$this->ownedBy(auth()->user()):false,
            'user_follow_state' => $this->user_follow_state,
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
            ->orderBy('comment_created_at' , 'desc')
            ->paginate(7);
    }
}