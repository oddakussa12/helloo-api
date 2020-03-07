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
use App\Repositories\Contracts\PostRepository;
use App\Repositories\Contracts\PostCommentRepository;

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
            'user_id'=>$this->user_id,
            'user_name'=>$this->user_name,
            'user_avatar'=>$this->user_avatar,
            'user_cover'=>$this->when($request->routeIs('my.profile')||$request->routeIs('user.show'),function (){
                return $this->user_cover;
            }),
            'user_gender'=>$this->user_gender,
            'user_about'=>$this->user_about,
            'user_score' => $this->user_score,
            'user_country'=>$this->user_country,
            'user_is_guest'=>$this->user_is_guest,
            'user_level'=>$this->user_level,
            'user_follow_state' => $this->when(!($request->routeIs('show.more.comment')||
                                                $request->routeIs('comment.myself')||
                                                $request->routeIs('user.name.search')||
                                                $request->routeIs('comment.store')||
                                                $request->routeIs('post.index')||
                                                $request->routeIs('post.top')||
                                                $request->routeIs('comment.mylike')||
                                                $request->routeIs('show.comment.by.post')||
                                                $request->routeIs('show.post.by.user')||
                                                $request->routeIs('notification.index')||
                                                $request->routeIs('user.ry.online.random')||
                                                $request->routeIs('show.locate.comment'))
                                                ||in_array('follow' , $include), function () use ($request){

                if($request->routeIs('user.rank')||$request->routeIs('post.index')||$request->routeIs('post.top')||$request->routeIs('post.myself')||$request->routeIs('show.post.by.user'))
                {
                    return $this->user_follow_state;
                }else{
                    return auth()->check()?auth()->user()->isFollowing($this->user_id):false;
                }
            }),
            'user_medal' => $this->when($request->routeIs('post.index')||$request->routeIs('post.top')||$request->routeIs('user.show') , function () use ($request){
                return $this->user_medal;
            }),
            'user_rank_score' => $this->when($request->routeIs('user.rank') , function () use ($request){
                return $this->user_rank_score;
            })
            ,$this->mergeWhen($request->routeIs('user.show'), function (){
                return collect([
                    'user_age'=> $this->user_age,
                    'userTags'=> UserTagCollection::collection($this->tags),
                    'user_gender'=>$this->user_gender,
                    'user_like_state'=>$this->isLiked($this->user_id),
                    'user_followme_count'=>$this->followers()->count(),
                    'user_myfollow_count'=>$this->followings()->count(),
                    'user_post_count'=>app(PostRepository::class)->getCountByUserId($this->user_id),
                    'user_comment_count'=>app(PostCommentRepository::class)->getCountByUserId($this->user_id),
                    'user_profile_like_num'=>$this->user_profile_like_num,
                    'user_picture'=>$this->user_picture
                ]);
            }),
        ];
    }
}