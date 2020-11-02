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
        $include = $request->input('include');
        $include = explode(',' ,$include);
        return [
            'user_id'           => $this->user_id,
            'user_name'         => $this->user_name,
            'user_nick_name'    => $this->user_nick_name,
            'user_avatar'       => $this->user_avatar_link,
            'user_country'      => $this->user_country,
            'user_continent'    => $this->user_continent,
            'user_level'        => $this->user_level,
            'user_follow_state' => $this->when($request->routeIs('user.show')||$request->routeIs('post.show')||in_array('follow' , $include), function () use ($request){
                if (isset($this->user_follow_state)) {
                    return $this->user_follow_state;
                } else {
                    return auth()->check()?auth()->user()->isFollowingUser($this->user_id):false;
                }
            }),

            //'friend_nick_name'=>!empty($this->friend_nick_name) ? $this->friend_nick_name : '',

            /*'friend_nick_name'=>$this->when(isset($this->friend_nick_name) , function (){
                return $this->friend_nick_name;
            }),*/
            'make_friend_created_at'=>$this->when(isset($this->make_friend_created_at) , function (){
               return $this->make_friend_created_at;
            }),
//            'user_medal' => $this->when($request->routeIs('post.index')||$request->routeIs('post.top')||$request->routeIs('user.show') , function () use ($request){
//                return $this->user_medal;
//            }),
//            'user_rank_score' => $this->when($request->routeIs('user.rank') , function () use ($request){
//                return $this->user_rank_score;
//            }),
            $this->mergeWhen($request->routeIs('user.show')||$request->routeIs('user.ry.online.refer'), function (){
                return collect([
                    'user_gender'=>$this->user_gender
                ]);
            }),
            $this->mergeWhen($request->routeIs('user.ry.online.planet')||$request->routeIs('user.ry.online.filter'), function (){
                return collect([
                    'user_about'=>$this->user_about
                ]);
            }),
            $this->mergeWhen($request->routeIs('user.show'), function (){
                return collect([
                    'user_about'            => $this->user_about,
                    'user_score'            => $this->user_score,
                    'user_birthday'         => $this->user_birthday,
                    'user_cover'            => $this->user_cover_link,
                    'user_like_state'       => $this->userProfileIsLiked($this->user_id),
                    'user_followme_count'   => $this->userFollowMeCount($this->user_id),
                    'user_myfollow_count'   => $this->userMyFollowCount($this->user_id),
                    'user_post_count'       => $this->userPostCount($this->user_id),
                    'user_comment_count'    => $this->userPostCommentCount($this->user_id),
                    'user_profile_like_num' => $this->user_profile_like_num,
                    'user_picture'          => $this->user_picture_link,
                    'userTags'              => UserTagCollection::collection($this->user_tags),
                    'view_status'           => $this->view_status ?? 0,
                    'view_count'            => $this->view_count,

//                    'user_followme_count'=>$this->followers()->count(),
//                    'user_myfollow_count'=>$this->followings()->count(),
//                    'user_post_count'=>app(PostRepository::class)->getCountByUserId($this->user_id),
//                    'user_comment_count'=>app(PostCommentRepository::class)->getCountByUserId($this->user_id),

                ]);
            }),
        ];
    }
}