<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 2019/5/19
 * Time: 18:35
 */
namespace App\Repositories\Eloquent;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Repositories\EloquentBaseRepository;
use App\Repositories\Contracts\UserRepository;

class EloquentUserRepository  extends EloquentBaseRepository implements UserRepository
{
    public function getDefaultPasswordField()
    {
        return $this->model->default_password_field;
    }
    public function getDefaultNameField()
    {
        return $this->model->default_name_field;
    }
    public function getDefaultEmailField()
    {
        return $this->model->default_email_field;
    }

    public function store($data)
    {
        return $this->model->create($data);
    }

    public function likePost($userId)
    {
        $user = $this->model->where('user_id', $userId)->first();
        return $user->likePost->pluck('pivot.post_like_state');
    }

    public function findOrFail($userId)
    {
        return $this->model->findOrFail($userId);
    }

    public function findOauth($oauth,$id)
    {
        return $this->model->where(array('user_'.$oauth=>$id))->first();
    }

    public function findMyFollow($object)
    {
        $followers = $object->followings()->orderByDesc('common_follows.created_at')->paginate(10,['*'],'follow_page');

        $userIds = $followers->pluck('user_id')->all(); //获取分页user id

        $followerIds = userFollow($userIds);//重新获取当前登录用户信息

        $followers->each(function ($item, $key) use ($followerIds) {
            $item->user_follow_state = in_array($item->user_id , $followerIds);
        });
        return $followers;
    }

    public function findByWhere($where)
    {
        return $this->model->where($where)->first();
    }

    public function findFollowMe($object)
    {
        $followers = $object->followers()->orderByDesc('common_follows.created_at')->paginate(10,['*'],'follow_page');

        $userIds = $followers->pluck('user_id')->all(); //获取分页user id

        $followerIds = userFollow($userIds);//重新获取当前登录用户信息

        $followers->each(function ($item, $key) use ($followerIds) {
            $item->user_follow_state = in_array($item->user_id , $followerIds);
        });
        
        return $followers;
    }

    public function getUserRank()
    {
        $activeUser = $this->getActiveUser();

        $userIds = $activeUser->pluck('user_id')->all();

        $followers = userFollow($userIds);

        $users = $this->getRankUserByUserId($userIds);

        $users->each(function($item , $key)use ($followers , $activeUser){
            $item->user_follow_state = in_array($item->user_id , $followers);
            $item->user_rank_score = $activeUser->where('user_id' , $item->user_id)->pluck('score')->first();
        });
        return $users->sortByDesc('user_rank_score')->values();
    }

    public function getActiveUser()
    {
        return Cache::rememberForever('user_rank', function() {
            $userId = collect();
            $userInfo = collect();
            $chinaNow = Carbon::now()->subDay(1);
            $post = DB::table('posts')
                ->whereNull('post_deleted_at')
                ->whereDate('post_created_at' , '>=' , date('Y-m-d 00:00:00' , strtotime($chinaNow)))
                ->whereDate('post_created_at' , '<=' , date('Y-m-d 23:59:59' , strtotime($chinaNow)))->groupBy('user_id')
                ->select(DB::raw('count(*) as post_num') , 'user_id')
                ->groupBy('user_id')
                ->orderBy('post_num' , 'desc')
                ->get();
            $postUserId =  $post->pluck('user_id');
            $userId = $userId->merge($postUserId);
            $comment = DB::table('posts_comments')
                ->whereNull('comment_deleted_at')
                ->whereDate('comment_created_at' , '>=' , date('Y-m-d 00:00:00' , strtotime($chinaNow)))
                ->whereDate('comment_created_at' , '<=' , date('Y-m-d 23:59:59' , strtotime($chinaNow)))->groupBy('user_id')
                ->select(DB::raw('count(*) as comment_num') , 'user_id')
                ->groupBy('user_id')
                ->orderBy('comment_num' , 'desc')
                ->get();
            $commentUserId =  $comment->pluck('user_id');
            $userId = $userId->merge($commentUserId);
            $like = DB::table('common_likes')
                ->whereDate('created_at' , '>=' , date('Y-m-d 00:00:00' , strtotime($chinaNow)))
                ->whereDate('created_at' , '<=' , date('Y-m-d 23:59:59' , strtotime($chinaNow)))->groupBy('user_id')
                ->select(DB::raw('count(*) as like_num') , 'user_id')
                ->groupBy('user_id')
                ->orderBy('like_num' , 'desc')
                ->get();
            $likeUserId =  $like->pluck('user_id');
            $userId = $userId->merge($likeUserId)->unique()->values();
            $userId->each(function ($item, $key) use(&$userInfo , $post , $comment , $like){
                $scoring = 0;
                $postCollect = $post->where('user_id' , $item)->first();
                $commentCollect = $comment->where('user_id' , $item)->first();
                $likeCollect = $like->where('user_id' , $item)->first();
                if(!empty($postCollect))
                {
                    $postNum = $postCollect->post_num;
                    $scoring += $postNum*2;
                }
                if(!empty($commentCollect))
                {
                    $commentNum = $commentCollect->comment_num;
                    $scoring += $commentNum*3;
                }
                if(!empty($likeCollect))
                {
                    $likeNum = $likeCollect->like_num;
                    $scoring += $likeNum*1;
                }
                $userInfo->put($item, collect(array('user_id'=>$item , 'score'=>$scoring)));
            });
            return $userInfo->sortByDesc('score')->take(10)->values();
        });
    }

    public function getYesterdayScoreByUserId($userId)
    {
        return Cache::remember('user_'.$userId.'_score', 300, function () use ($userId){
            $chinaNow = Carbon::now()->subDay(1);
            $postCount = DB::table('posts')
                ->where('user_id' , $userId)
                ->whereDate('post_created_at' , '>=' , date('Y-m-d 00:00:00' , strtotime($chinaNow)))
                ->whereDate('post_created_at' , '<=' , date('Y-m-d 23:59:59' , strtotime($chinaNow)))
                ->whereNull('post_deleted_at')
                ->count();
            $commentCount = DB::table('posts_comments')
                ->where('user_id' , $userId)
                ->whereDate('comment_created_at' , '>=' , date('Y-m-d 00:00:00' , strtotime($chinaNow)))
                ->whereDate('comment_created_at' , '<=' , date('Y-m-d 23:59:59' , strtotime($chinaNow)))
                ->whereNull('comment_deleted_at')
                ->count();
            $likeCount = DB::table('common_likes')
                ->where('user_id' , $userId)
                ->whereDate('created_at' , '>=' , date('Y-m-d 00:00:00' , strtotime($chinaNow)))
                ->whereDate('created_at' , '<=' , date('Y-m-d 23:59:59' , strtotime($chinaNow)))->groupBy('user_id')
                ->count();
            $score = $commentCount*3+$postCount*2+$likeCount;
            return $score;
        });
}

    public function getUserRankByUserId($userId)
    {
        return collect(DB::select("SELECT b.rank FROM (SELECT t.*, @rank := @rank + 1 AS rank FROM (SELECT @rank := 0) r,(SELECT * FROM f_users ORDER BY user_score DESC) AS t) AS b WHERE b.user_id = ?;", [$userId]))->pluck('rank')->first();
//        return Cache::remember('user_'.$userId.'_rank', 5, function () use ($userId){
//            return collect(DB::select("SELECT b.rank FROM (SELECT t.*, @rank := @rank + 1 AS rank FROM (SELECT @rank := 0) r,(SELECT * FROM f_users ORDER BY user_score DESC) AS t) AS b WHERE b.user_id = ?;", [$userId]))->pluck('rank')->first();
//        });
    }




    public function getActiveUserId()
    {
        $activeUser = $this->getActiveUser();
        return $activeUser->pluck('score' , 'user_id')->all();
    }


    public function getRankUserByUserId($userIds){
        return $this->model->whereIn('user_id',$userIds)->get();
    }
}
