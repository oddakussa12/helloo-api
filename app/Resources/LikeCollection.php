<?php


namespace App\Resources;

use App\Models\PostComment;
use Illuminate\Http\Resources\Json\Resource;

class LikeCollection extends Resource
{
    public function toArray($request)
    {
        $likable = $this->likable;
        if($likable instanceof PostComment)
        {
            return new PostCommentCollection($likable);
        }elseif ($likable instanceof Post)
        {
            return new PostCollection($likable);
        }
    }
}