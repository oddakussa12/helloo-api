<?php

/**
 * @Author: Dell
 * @Date:   2019-08-19 16:47:05
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-10-22 16:25:24
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
        return [
            'user_id'=>$this->user_id,
            'user_about'=>$this->user_about,
            'user_name'=>$this->user_name,
            'user_gender'=>$this->user_gender,
            'user_language'=>$this->user_language,
            'user_avatar'=>$this->user_avatar,
            'user_cover'=>$this->user_cover,
            'user_country'=>$this->user_country,
            'user_follow_state' => auth()->check()?auth()->user()->isFollowing($this->user_id):false,
            'user_followme_count' =>$this->followers()->count(),
            'user_myfollow_count' => $this->followings()->count(),
        ];
    }
}