<?php


namespace App\Resources;

use App\Traits\CachablePost;
use Illuminate\Http\Resources\Json\Resource;

class PostSearchPaginateCollection extends Resource
{
    use CachablePost;


    /**
     * @param $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'post_uuid'          => $this->resource['post_uuid'],
            'post_media'         => postMedia($this->resource['post_type'],$this->resource['post_media']),
            'post_title'         => $this->resource['post_content'],
            'post_default_title' => $this->resource['post_content'],
            'post_index_locale'  => $this->resource['post_content_default_locale'],
            'post_type'          => $this->resource['post_type'],
            'post_comment_num'   => $this->resource['post_comment_num'] ??'',
            'post_like_state'    => $this->resource['likeState'] ?? '',
            'post_dislike_state' => $this->resource['dislikeState'] ?? '',
            'post_event_country' => $this->resource['post_event_country'] ?? '',
            'post_created_at'    => $this->resource['create_at'], //optional($this->resource['post_id'])->toDateTimeString(),

            'post_country'       => $this->countryCount($this->resource['post_id']),
            'postLike'           => $this->likeCount($this->resource['post_id']),
            'topics'             => $this->getPostTopics($this->resource['post_id']),

            'owner'              => !empty($this->resource['owner']) ? new UserCollection($this->resource['owner']) : [],
            'vote_info'          => !empty($this->resource['voteInfo']) ? PostVoteCollection::collection($this->resource['voteInfo']) : [],
            'post_format_created_at' => dateTrans($this->resource['create_at']),
        ];
    }

}