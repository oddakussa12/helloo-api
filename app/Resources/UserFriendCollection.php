<?php


namespace App\Resources;

use Illuminate\Http\Resources\Json\Resource;

class UserFriendCollection extends Resource
{
    public function toArray($request)
    {
        return $this->resource;
    }
}