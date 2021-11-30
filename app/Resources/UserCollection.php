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
        $resource = collect($this->resource);
        if($resource->has('user_id'))
        {
            $resource->put('user_id' , strval($resource->get('user_id')));
        }
        if($resource->has('user_gender'))
        {
            $resource->put('user_gender' , intval($resource->get('user_gender')));
        }
        if($resource->has('user_level'))
        {
            $resource->put('user_level' , intval($resource->get('user_level')));
        }
        if($resource->has('user_shop'))
        {
            $resource->put('user_shop' , intval($resource->get('user_shop')));
        }
        if($resource->has('user_answer'))
        {
            $resource->put('user_answer' , intval($resource->get('user_answer')));
        }
        if($resource->has('user_activation'))
        {
            $resource->put('user_activation' , intval($resource->get('user_activation')));
        }
        if($resource->has('user_delivery'))
        {
            $resource->put('user_delivery' , boolval($resource->get('user_delivery')));
        }
        if($resource->has('user_tag'))
        {
            $resource->forget('user_tag');
        }
        if($resource->has('open_time'))
        {
            $resource->put('open_time' , \Carbon\Carbon::parse($resource->get('open_time'))->format('H:i A'));
        }
        if($resource->has('close_time'))
        {
            $resource->put('close_time' , \Carbon\Carbon::parse($resource->get('close_time'))->format('H:i A'));
        }
        return $resource->toArray();
    }
}