<?php

/**
 * @Author: Dell
 * @Date:   2019-08-19 16:47:05
 * @Last Modified by:   Gxc
 * @Last Modified time: 2019-10-28 14:15:40
 */
namespace App\Resources;

use App\Traits\CachableUser;
use Illuminate\Http\Resources\Json\Resource;

class UserCollection extends Resource
{
    use CachableUser;
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'user_id'           => $this->user_id,
            'user_name'         => $this->user_name,
            'user_nick_name'    => $this->user_nick_name,
            'user_avatar'       => $this->user_avatar_link,
            'user_country'      => $this->user_country,
            'user_continent'    => $this->user_continent,
            'user_level'        => $this->user_level,
            $this->mergeWhen($request->routeIs('user.show')||$request->routeIs('user.ry.online.refer'), function (){
                return collect([
                    'user_gender'=>$this->user_gender
                ]);
            }),
            $this->mergeWhen($request->routeIs('user.ry.online.planet')||$request->routeIs('user.ry.online.filter'), function (){
                return collect([
                    'user_about'=>$this->user_about
                ]);
            })
        ];
    }
}