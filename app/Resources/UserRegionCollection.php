<?php


namespace App\Resources;

use Illuminate\Http\Resources\Json\Resource;

class UserRegionCollection extends Resource
{
    public function toArray($request)
    {
        return [
            'region_id'=>$this->region_id,
            'region_slug'=>$this->region_slug,
        ];
    }
}