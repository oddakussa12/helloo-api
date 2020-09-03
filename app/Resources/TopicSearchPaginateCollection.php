<?php


namespace App\Resources;


use Illuminate\Http\Resources\Json\Resource;

class TopicSearchPaginateCollection extends Resource
{

    /**
     * @param $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'post_num' => rand(1000, 100000),
            'flag'     => $this->when(true, function (){
                return $this->resource['flag'] ?? 0;
            }),
            'topic_content' => $this->resource['topic_content'],
        ];
    }
}