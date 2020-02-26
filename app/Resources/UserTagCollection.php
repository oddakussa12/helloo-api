<?php


namespace App\Resources;

use Illuminate\Http\Resources\Json\Resource;

class UserTagCollection extends Resource
{
    public function toArray($request)
    {
        return [
            'tag_id'=>$this->tag_id,
            'tag_slug'=>$this->tag_slug,
        ];
    }
}