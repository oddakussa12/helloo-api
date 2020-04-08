<?php


namespace App\Resources;

use App\Traits\CachablePost;
use Illuminate\Http\Resources\Json\Resource;

class PostPaginateCollection extends Resource
{
    use CachablePost;
    /**
     *
     */
    public function toArray($request)
    {
        return [
            'post_uuid' => $this->post_uuid,
            'post_media' => $this->post_mutation_media,
            'post_default_locale' => $this->post_default_locale,
            'post_index_locale' => $this->post_index_locale,
            'post_title' => $this->post_index_title,
            'post_type' => $this->post_type,
            'post_comment_num' => $this->post_comment_num,
            'post_view_num' => $this->viewVirtualCount($this->post_id),
            'topTwoComments'=> $this->when(isset($this->topTwoComments) , function (){
                return PostCommentCollection::collection($this->topTwoComments)->sortByDesc('comment_like_temp_num')->values()->all();
            }),
//            'post_country'=> collect([
//                'total'=>collect($this->countryNum)->get('country_num' , 0),
//                'data'=>$this->countries
//            ]),
            'post_country'=> $this->countryCount($this->post_id),

            'post_created_at'=> optional($this->post_created_at)->toDateTimeString(),
            'post_format_created_at'=> $this->post_format_created_at,

            'owner'=>$this->when(!$request->has('keywords') , function (){
                return new UserCollection($this->owner);
            }),
            'post_owner' =>$this->when(!$request->has('keywords') , function (){
                return $this->post_owner;
            }),
            'postLike' =>$this->likeCount($this->post_id),
            'post_like_state'=>$this->post_like_state,
            'post_dislike_state'=>$this->post_dislike_state,
            'post_event_country'=>$this->post_event_country,

        ];
    }

}