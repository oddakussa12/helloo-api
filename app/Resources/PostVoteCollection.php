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
        $userId = auth()->check() ? auth()->user()->user_id : null;
        $count  = $this->voteChoose($this->post_id, $this->id, $userId);

        return [
            'tab_name'       => $this->tab_name,
            'content'        => $this->content,
            'count'          => $count['count'],
            'is_choose'      => $count['choose'],
            'vote_detail_id' => $this->id,
        ];
    }

}