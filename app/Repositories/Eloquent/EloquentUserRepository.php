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
        $userIds = $this->getActiveUserId();

        $followers = userFollow($userIds);

        $users = $this->getUserRankByUserId($userIds);

        $users->each(function($item , $key)use ($followers){
            $item->user_follow_state = in_array($item->user_id , $followers);
        });
        return $users;
    }

    public function getActiveUserId()
    {
        return Cache::rememberForever('user_rank', function() {
            $userId = collect();
            $userInfo = collect();
            $chinaNow = Carbon::now()->subDay(1);
            $post = DB::table('posts')
                ->whereDate('post_created_at' , '>=' , date('Y-m-d 00:00:00' , strtotime($chinaNow)))
                ->whereDate('post_created_at' , '<=' , date('Y-m-d 23:59:59' , strtotime($chinaNow)))->groupBy('user_id')
                ->select(DB::raw('count(*) as post_num') , 'user_id')
                ->groupBy('user_id')
                ->orderBy('post_num' , 'desc')
                ->get();
            $postUserId =  $post->pluck('user_id');
            $userId = $userId->merge($postUserId);
            $comment = DB::table('posts_comments')
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
                $postCollect = $post->where('user_id' , $item)->first();
                $commentCollect = $comment->where('user_id' , $item)->first();
                $likeCollect = $like->where('user_id' , $item)->first();
                if(!empty($postCollect))
                {
                    $postNum = $postCollect->post_num;
                    $scoring =+ $postNum*2;
                }
                if(!empty($commentCollect))
                {
                    $commentNum = $commentCollect->comment_num;
                    $scoring =+ $commentNum*3;
                }
                if(!empty($likeCollect))
                {
                    $likeNum = $likeCollect->like_num;
                    $scoring =+ $likeNum*1;
                }
                $userInfo->put($item, collect(array('user_id'=>$item , 'score'=>$scoring)));
            });
            return $userInfo->sortByDesc('score')->values()->take(10)->pluck('user_id');
        });
    }


    public function getUserRankByUserId($userIds){
        return $this->model->whereIn('user_id',$userIds)->get();
    }
}
