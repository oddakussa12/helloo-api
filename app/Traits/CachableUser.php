<?php

namespace App\Traits;


use Illuminate\Support\Facades\Redis;

trait CachableUser
{
    /**
     * 获取用户profile被点赞数量
     *
     * @param int $id
     * @return int
     */

    public function userProfileLikeCount($id)
    {
        $userProfileLikesKey = config('redis-key.user.profile_likes');
        return intval(Redis::zscore($userProfileLikesKey , $id));
    }

    /**
     * 获取用户积分排行
     *
     * @param int $id
     * @return int
     */

    public function userScoreRank($id)
    {
        $userScoreRankKey = config('redis-key.user.score_rank');
        $rank = intval(Redis::zrevrank($userScoreRankKey , $id));
        return $rank*config('common.user_rank_coefficient')-config('common.user_rank_add_num');
    }

    /**
     * 获取用户点赞贴子数量
     *
     * @param int $id
     * @return int
     */

    public function userPostLikeCount($id)
    {
        $userPostLikesKey = config('redis-key.user.post_likes');
        return intval(Redis::zscore($userPostLikesKey , $id));
    }

    /**
     * 获取用户踩贴子数量
     *
     * @param int $id
     * @return int
     */

    public function userPostDislikeCount($id)
    {
        $userPostDislikesKey = config('redis-key.user.post_dislikes');
        return intval(Redis::zscore($userPostDislikesKey , $id));
    }

    /**
     * 获取用户点赞评论数量
     *
     * @param int $id
     * @return int
     */

    public function userPostCommentLike($id)
    {
        $userPostCommentLikesKey = config('redis-key.user.post_comment_likes');
        return intval(Redis::zscore($userPostCommentLikesKey , $id));
    }

    /**
     * 获取用户贴子数量
     *
     * @param int $id
     * @return int
     */

    public function userPostCount($id)
    {
        $userPostsKey = config('redis-key.user.posts');
        return intval(Redis::zscore($userPostsKey , $id));
    }


    /**
     * 获取用户贴子评论数量
     *
     * @param int $id
     * @return int
     */

    public function userPostCommentCount($id)
    {
        $userPostCommentsKey = config('redis-key.user.post_comments');
        return intval(Redis::zscore($userPostCommentsKey , $id));
    }


    /**
     * 获取用户粉丝数量
     *
     * @param int $id
     * @return int
     */

    public function userFollowMeCount($id)
    {
        $userFollowMesKey = config('redis-key.user.follow_me');
        return intval(Redis::zscore($userFollowMesKey , $id));
    }


    /**
     * 获取用户关注数量
     *
     * @param int $id
     * @return int
     */

    public function userMyFollowCount($id)
    {
        $userMyFollowsKey = config('redis-key.user.my_follow');
        return intval(Redis::zscore($userMyFollowsKey , $id));
    }


    /**
     * 更新用户积分排行
     *
     * @param int $id
     * @param int $score
     * @return int
     */

    public function updateUserScoreRank($id , int $score=1)
    {
        $userScoreRankKey = config('redis-key.user.score_rank');
        return intval(Redis::zincrby($userScoreRankKey , $score , $id));
    }



    /**
     * 更新用户点赞贴子数量
     *
     * @param int $id
     * @param int $score
     * @return int
     */

    public function updateUserPostLikeCount($id , int $score=1)
    {
        $userPostLikesKey = config('redis-key.user.post_likes');
        return intval(Redis::zincrby($userPostLikesKey , $score , $id));
    }

    /**
     * 更新用户踩贴子数量
     *
     * @param int $id
     * @param int $score
     * @return int
     */

    public function updateUserPostDislikeCount($id , int $score=1)
    {
        $userPostDislikesKey = config('redis-key.user.post_dislikes');
        return intval(Redis::zincrby($userPostDislikesKey , $score , $id));
    }


    /**
     * 更新用户点赞贴子评论数量
     *
     * @param int $id
     * @param int $score
     * @return int
     */

    public function updateUserPostCommentLikeCount($id , int $score=1)
    {
        $userPostCommentsLikesKey = config('redis-key.user.post_comment_likes');
        return intval(Redis::zincrby($userPostCommentsLikesKey , $score , $id));
    }


    /**
     * 更新用户贴子数量
     *
     * @param int $id
     * @param int $score
     * @return int
     */

    public function updateUserPostCount($id , int $score=1)
    {
        $userPostsKey = config('redis-key.user.posts');
        return intval(Redis::zincrby($userPostsKey , $score , $id));
    }


    /**
     * 更新用户贴子评论数量
     *
     * @param int $id
     * @param int $score
     * @return int
     */

    public function updateUserPostCommentCount($id , int $score=1)
    {
        $userPostCommentsKey = config('redis-key.user.post_comments');
        return intval(Redis::zincrby($userPostCommentsKey , $score , $id));
    }

    /**
     * 更新用户粉丝数量
     *
     * @param int $id
     * @param int $score
     * @return int
     */

    public function updateUserFollowMeCount($id , int $score=1)
    {
        $userFollowMesKey = config('redis-key.user.follow_me');
        return intval(Redis::zincrby($userFollowMesKey , $score , $id));
    }

    /**
     * 更新用户关注数量
     *
     * @param int $id
     * @param int $score
     * @return int
     */

    public function updateUserMyFollowCount($id , int $score=1)
    {
        $userMyFollowsKey = config('redis-key.user.my_follow');
        return intval(Redis::zincrby($userMyFollowsKey , $score , $id));
    }

    public function storeProfileLikeMe($likeUserId , $id , int $likeTime=0 , int $score=1)
    {
        $userProfileLikeKey = 'user.'.$id.'.profile.like';
        $userProfileLikesKey = 'user.profile.likes';
        $score = $likeTime==0?-1:$score;
        if($score>0)
        {
            Redis::zadd($userProfileLikeKey , $likeTime , $likeUserId);
        }else{
            Redis::zrem($userProfileLikeKey , $likeUserId);
        }
        Redis::zIncrBy($userProfileLikesKey , $score , $id);

    }

    public function userProfileIsLiked($id , $likeUserId=null)
    {
        $likeUserId = $likeUserId===null?auth()->id():$likeUserId;
        $userProfileLikeKey = 'user.'.$id.'.profile.like';
        return (bool)($likeUserId&&Redis::zrank($userProfileLikeKey , $likeUserId)!==null);
    }
}
