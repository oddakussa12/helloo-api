<?php

namespace App\Traits;


use Illuminate\Support\Facades\Redis;

trait CachableUser
{
    public function likeCount($id)
    {
        $userProfileLikesKey = 'user.profile.likes';
        if(Redis::exists($userProfileLikesKey))
        {
            $rank = intval(Redis::zscore($userProfileLikesKey , $id));
        }else{
            $rank = 0;
        }
        return $rank;
    }

    public function isLiked($id)
    {
        $likeUserId = auth()->id();
        $userProfileLikeKey = 'user.'.$id.'.profile.like';
        return Redis::zrank($userProfileLikeKey , $likeUserId)===null||!(auth()->check())?false:true;
    }
}
