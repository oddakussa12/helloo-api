<?php


namespace App\Resources;

use App\Traits\CachablePost;
use Illuminate\Http\Resources\Json\Resource;

class PostPaginateCollection extends Resource
{
    use CachablePost;


    /**
     * @param $request
     * @return array
     */
    public function toArray($request)
    {
        $user = auth()->user();
        return [
            'post_id'            => $this->resource['post_id'],
            'post_uuid'          => $this->resource['post_uuid'],
            'post_media'         => $this->resource['post_media'],
            //'post_default_locale'=> $this->resource['post_default_locale'],
            //'post_index_locale'  => $this->resource['post_index_locale'],
            'post_title'         => $this->resource['post_content'],
            //'post_default_title' => $this->resource['post_origin_index_title'],
            'post_type'          => $this->resource['post_type'],
            'post_comment_num'   => $this->resource['post_comment_num'] ??'',
            'post_like_state'    => $this->resource['likeState'] ?? '',
            'post_dislike_state' => $this->resource['dislikeState'] ?? '',
            'post_event_country' => $this->resource['post_event_country'] ?? '',
            'post_country'       => $this->countryCount($this->resource['post_id']),
            'postLike'           => $this->likeCount($this->resource['post_id']),
            'post_view_num'      => $this->viewVirtualCount($this->resource['post_id']),
            'post_created_at'    => optional($this->resource['post_id'])->toDateTimeString(),

            //'post_format_created_at' => $this->resource['post_format_created_at'],
            /*'topTwoComments'=> $this->when(isset($this->topTwoComments) , function (){
                return PostCommentCollection::collection($this->topTwoComments)->sortByDesc('comment_like_temp_num')->values()->all();
            }),*/



            'owner' => new UserCollection($user),

            /*'post_owner' =>$this->when(!$request->has('keywords') , function (){
                return $this->post_owner;
            }),*/


        ];
    }
    /**
     * @param $request
     * @return array
     */
    public function toArray2($request)
    {
        return [
            'post_id'            => $this->resource['post_id'],
            'post_uuid'          => $this->resource['post_uuid'],
            'post_media'         => $this->resource['post_media'],
            //'post_default_locale'=> $this->resource['post_default_locale'],
            //'post_index_locale'  => $this->resource['post_index_locale'],
            'post_title'         => $this->resource[$this->resource['post_content_default_locale']] ?? $this->resource['en'],
            //'post_default_title' => $this->resource['post_origin_index_title'],
            'post_type'          => $this->resource['post_type'],
            'post_comment_num'   => $this->resource['post_comment_num'] ??'',
            'post_like_state'    => $this->resource['likeState'] ?? '',
            'post_dislike_state' => $this->resource['dislikeState'] ?? '',
            'post_event_country' => $this->resource['post_event_country'] ?? '',
            'post_country'       => $this->countryCount($this->resource['post_id']),
            'postLike'           => $this->likeCount($this->resource['post_id']),
            'post_view_num'      => $this->viewVirtualCount($this->resource['post_id']),
            'post_created_at'    => optional($this->resource['post_id'])->toDateTimeString(),

            //'post_format_created_at' => $this->resource['post_format_created_at'],
            /*'topTwoComments'=> $this->when(isset($this->topTwoComments) , function (){
                return PostCommentCollection::collection($this->topTwoComments)->sortByDesc('comment_like_temp_num')->values()->all();
            }),*/



            'owner' => new UserCollection(auth()->user()),

            /*'post_owner' =>$this->when(!$request->has('keywords') , function (){
                return $this->post_owner;
            }),*/


        ];
    }
}