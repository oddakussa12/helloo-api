<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

trait CachableUser
{

    public function initUser(User $user , array $extend=array())
    {
        $userKey = 'user.'.$user->getKey().'.data';
        $data = array(
            'user_id'=>$user->user_id,
            'user_email'=>$user->user_email,
            'user_name'=>$user->user_name,
            'user_nick_name'=>$user->user_nick_name,
            'user_gender'=>-1,
            'user_avatar'=>'default_avatar.jpg', //默认头像
            'user_cover'=>'default_cover.png',
            'user_country_id'=>$user->user_country_id,
            'user_age'=>0,
            'user_level'=>0,
            'user_created_at'=>optional($user->user_created_at)->timestamp
        );
        $data = $data+$extend;
        Redis::hmset($userKey , $data);
    }

    public function getFriend($user)
    {
        $memKey   = config('redis-key.user.user_friend').'_'.$user->user_id;
        $memValue = Redis::get($memKey);
        if (!empty($memValue)) {
            $data = json_decode($memValue, true);
        } else {

            $result = DB::select('select sum(country_count) friend_count, count(user_country_id) friend_country from (
            SELECT count(user_id) country_count, user_country_id from f_users where user_id in(
            select friend_id from f_users_friends where user_id=?) group by user_country_id) b', [31666]);
            $data = !empty($result) ? (array)current($result) : [];
            Redis::set($memKey, $memValue);
            Redis::expire($memKey, 86400*7);
        }
        foreach($data as $key=>$val) {
            $user->$key=(int)$val;
        }

        return $user;

    }

    public function getUser(int $id , $fields=array())
    {
        $userKey = 'user.'.$id.'.data';
        if(!empty($fields))
        {
            return array_combine($fields , Redis::hmget($userKey , $fields));
        }
        return Redis::hgetall($userKey);
    }

    public function isCanUpdateName(int $id)
    {
        $lastUpdateAt = $this->getUser($id , array('user_name_updated_at'));
        $data = empty($lastUpdateAt['user_name_updated_at'])?'1546272000':$lastUpdateAt['user_name_updated_at'];
        return (int)(Carbon::createFromTimestamp($data)->addDay(config('common.user_name_update_time'))->isPast());
    }

    public function updateUser(User $user , array $extend=array())
    {
        $userKey = 'user.'.$user->getKey().'.data';
        $data = array(
            'user_nick_name'=>$user->user_nick_name,
            'user_gender'=>$user->user_gender,
            'user_avatar'=>$user->user_avatar, //默认头像
            'user_country_id'=>$user->user_country_id,
            'user_age'=>$user->user_age,
            'user_birthday'=>$user->user_birthday,
            'user_level'=>$user->user_level,
        );
        $data = $data+$extend;
        Redis::hmset($userKey , $data);
    }


    /**
     * 获取用户profile被点赞数量
     *
     * @param $userName
     * @param $userEmail
     * @param bool $op
     * @return void
     */

    public function updateUserLists($userName, $userEmail='' , $op=true)
    {
        $userNameKey = config('redis-key.user.user_name');
        $userEmailKey = config('redis-key.user.user_email');
        if($op)
        {
            !blank($userName)&&Redis::sadd($userNameKey , mb_convert_case($userName, MB_CASE_LOWER, "UTF-8"));
            !blank($userEmail)&&Redis::sadd($userEmailKey , mb_convert_case($userEmail, MB_CASE_LOWER, "UTF-8"));
        }else{
            !blank($userName)&&Redis::srem($userNameKey , mb_convert_case($userName, MB_CASE_LOWER, "UTF-8"));
            !blank($userEmail)&&Redis::srem($userEmailKey , mb_convert_case($userEmail, MB_CASE_LOWER, "UTF-8"));
        }
    }

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
        if($rank===0)
        {
            $rank = $id;
        }
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
        $count = intval(Redis::zscore($userFollowMesKey , $id));
        return $count >0 ? $count : 0;
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
        $count = intval(Redis::zscore($userMyFollowsKey , $id));
        return $count >0 ? $count : 0;
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

    public function isBlocked($userId)
    {
        $key = 'block_user';
        $time = Redis::zscore($key , $userId);
        return !blank($time)&&time()-$time<=43200*60;
    }

    public function userProfileViewCount()
    {
        return mt_rand(100 , 10000);
    }
}
