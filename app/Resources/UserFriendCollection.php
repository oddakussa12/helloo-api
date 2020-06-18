<?php


namespace App\Resources;

use Illuminate\Http\Resources\Json\Resource;

class UserFriendCollection extends Resource
{
    public function toArray($request)
    {
        return [
            'id'=>$this->id,
            'created_at'=>optional($this->created_at)->toDateTimeString(),
            'friend'=>new UserCollection($this->friend),
        ];
    }
}