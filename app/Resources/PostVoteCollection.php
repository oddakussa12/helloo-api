<?php


namespace App\Resources;

use App\Traits\CachablePost;
use Illuminate\Http\Resources\Json\Resource;

class PostVoteCollection extends Resource
{
    use CachablePost;
    /**
     *
     */
    public function toArray($request)
    {
        $isChoose = $this->voteChoose($this->post_id, $this->id);
        return [
            'tab_name' => $this->tab_name,
            'vote_detail_id' => $this->id,
            'content' => $this->content,
            'count' => rand(10, 1000),
            'is_choose' => false,
        ];
    }


}