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
            'post_uuid' => $this->post_uuid,
            'post_media' => $this->post_media,
            'post_default_locale' => $this->post_default_locale,
            'post_title' => $this->post_index_title,
            'post_type' => $this->post_type,
            'post_comment_num' => $this->post_comment_num,
            'post_view_num' => $this->when($this->relationLoaded('viewCount') , function(){
                return $this->post_view_num;
            }),
            'topTwoComments'=> $this->when(isset($this->topTwoComments) , function (){
                return PostCommentCollection::collection($this->topTwoComments)->sortByDesc('comment_like_temp_num')->values()->all();
            }),
            'post_country'=> collect([
                'total'=>collect($this->countryNum)->get('country_num' , 0),
                'data'=>$this->countries
            ]),

            'post_created_at'=> optional($this->post_created_at)->toDateTimeString(),
            'post_format_created_at'=> $this->post_format_created_at,

            'owner'=>$this->when(!$request->has('keywords') , function (){
                return new UserCollection($this->owner);
            }),
            'post_owner' =>$this->when(!$request->has('keywords') , function (){
                return $this->post_owner;
            }),

        ];
    }

}