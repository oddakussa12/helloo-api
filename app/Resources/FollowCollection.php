<?php

/**
 * @Author: Dell
 * @Date:   2019-10-21 18:52:03
 * @Last Modified by:   Dell
 * @Last Modified time: 2019-10-21 21:18:49
 */
namespace App\Resources;

use App\Models\PostComment;
use Illuminate\Http\Resources\Json\Resource;

class FollowCollection extends Resource
{
    public function toArray($request)
    {
        $user = $this->user;
        return [
        	'user_id'=>$user->user_id,
//        	'user_uuid'=>$this->user_uuid,
        	'user_name'=>$user->user_name,
        	'user_nick_name'=>$user->user_nick_name,
//        	'user_email'=>$this->user_email,
        	'user_avatar'=>$user->user_avatar_link,
//        	'user_country_id'=>$this->user_country_id,
        	'user_country'=>$user->user_country,
//        	'user_follow_time'=>$this->pivot->created_at,
        	'user_follow_state'=>$user->user_follow_state
        ];

    }
}