<?php


namespace App\Resources;

use App\Traits\CachablePost;
use Illuminate\Http\Resources\Json\Resource;

class TopicSearchPaginateCollection extends Resource
{
   // use CachableTopic;

    /**
     * @param $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'post_num' => rand(1000, 100000),
            'topic_content' => $this->resource['topic_content'],
        ];
    }
}