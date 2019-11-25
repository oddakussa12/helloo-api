<?php

/**
 * @Author: Dell
 * @Date:   2019-08-19 16:47:05
 * @Last Modified by:   Gxc
 * @Last Modified time: 2019-10-28 14:15:40
 */
namespace App\Resources;

use Illuminate\Http\Resources\Json\Resource;
use App\Repositories\Contracts\PostRepository;
use App\Repositories\Contracts\PostCommentRepository;

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
            'user_name'=>$this->user_name,
            'user_avatar'=>$this->user_avatar,
            'user_score' => $this->user_score,
            'user_country'=>$this->user_country,
            'user_language'=>$this->user_language,
            'user_is_guest'=>$this->user_is_guest,
            'user_follow_state' => $this->when(!($request->routeIs('show.post.by.user')||$request->routeIs('notification.index')) , function () use ($request){
                if($request->routeIs('user.rank')||$request->routeIs('post.index')||$request->routeIs('post.top')||$request->routeIs('post.myself')||$request->routeIs('show.post.by.user'))
                {
                    return $this->user_follow_state;
                }else{
                    return auth()->check()?auth()->user()->isFollowing($this->user_id):false;
                }
            }),
            'user_followme_count' =>$this->when($request->routeIs('user.show') , function (){
                return $this->followers()->count();
            }),
            'user_myfollow_count' =>$this->when($request->routeIs('user.show') , function (){
                return $this->followings()->count();
            }),
            'user_post_count' => $this->when($request->routeIs('user.show') , function () use($request){
                return app(PostRepository::class)->getCountByUserId($this->user_id);
            }),
            'user_comment_count' => $this->when($request->routeIs('user.show') , function () use($request){
                return app(PostCommentRepository::class)->getCountByUserId($this->user_id);
            }),
            'user_medal' => $this->when($request->routeIs('post.index')||$request->routeIs('post.top')||$request->routeIs('user.show') , function () use ($request){
                return $this->user_medal;
            }),
            'user_rank_score' => $this->when($request->routeIs('user.rank') , function () use ($request){
                return $this->user_rank_score;
            })
        ];
    }
}