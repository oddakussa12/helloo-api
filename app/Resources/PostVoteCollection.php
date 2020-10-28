<?php


namespace App\Resources;

use App\Traits\CachablePost;
use Illuminate\Http\Resources\Json\Resource;

class PostVoteCollection extends Resource
{
    use CachablePost;

    /**
     * @param $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'tab_name'       => $this->tab_name,
            'content'        => $this->content,
            'count'          => $this->count,
            'is_choose'      => $this->choose,
            'country'        => $this->country,
            'vote_detail_id' => $this->id,
        ];
    }

}