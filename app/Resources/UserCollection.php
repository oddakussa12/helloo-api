<?php

/**
 * @Author: Dell
 * @Date:   2019-08-19 16:47:05
 * @Last Modified by:   Gxc
 * @Last Modified time: 2019-10-28 14:15:40
 */
namespace App\Resources;


use Illuminate\Http\Resources\Json\Resource;

class UserCollection extends Resource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $resource = $this->resource;
//        if($request->routeIs('user.show')||$request->routeIs('my.profile'))
//        {
//            $resource->likedCount = 0;
//            $resource->friendCount = 0;
//        }
        return $this->resource;
    }
}