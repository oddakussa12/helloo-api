<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 2019/5/19
 * Time: 18:35
 */
namespace App\Repositories\Eloquent;

use Carbon\Carbon;
use App\Models\Post;
use App\Models\Like;
use App\Models\PostComment;
use App\Models\YesterdayScore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
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
        $rankTopTenUser = $this->getYesterdayUserRank();

        $userIds = $rankTopTenUser->pluck('user_id')->all();

        $followers = userFollow($userIds);

        $rankTopTenUser->each(function($item , $key)use ($followers){
            $item->user_follow_state = in_array($item->user_id , $followers);
        });
        return $rankTopTenUser->sortByDesc('user_rank_score')->values();
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
            $userId = DB::table('users')
                ->whereIn('user_id' , $userId)
                ->where('user_is_guest' , 0)
                ->select('user_id')
                ->pluck('user_id');
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


    public function getUserYesterdayRankByUserId($userId)
    {
        return Cache::remember('user_yesterday_rank_'.$userId, 5, function () use ($userId){
            $chinaNow = Carbon::now()->subDay(1);
            $sql = <<<DOC
SELECT
	b.user_rank_score , b.rank
FROM
	(
		SELECT
			t.*, @rank := @rank + 1 AS rank
		FROM
			(SELECT @rank := 0) r,
			(
				SELECT
					f_users.*,f_yesterday_scores.user_score as user_rank_score
				FROM
					f_yesterday_scores
				INNER JOIN f_users on f_yesterday_scores.user_id=f_users.user_id
				where f_users.user_is_guest=0
				and f_yesterday_scores.rank_date=?
				ORDER BY
					f_yesterday_scores.user_score DESC,f_yesterday_scores.user_id DESC
			) AS t
	) AS b
WHERE
	b.user_id = ?
DOC;
            return collect(DB::select($sql, [date('Y-m-d' ,strtotime($chinaNow)),$userId]))->first();
        });
    }

    public function getUserRankByUserId($userId)
    {
        $rank = Cache::remember('user_'.$userId.'_rank', 5, function () use ($userId){
            return collect(DB::select("SELECT b.rank FROM (SELECT t.*, @rank := @rank + 1 AS rank FROM (SELECT @rank := 0) r,(SELECT * FROM f_users ORDER BY user_score DESC) AS t) AS b WHERE b.user_id = ?;", [$userId]))->pluck('rank')->first();
        });
        return $rank*99;
    }




    public function getActiveUserId()
    {
        $activeUser = $this->getYesterdayUserRank();
        return $activeUser->pluck('user_rank_score' , 'user_id')->all();
    }


    public function getYesterdayUserRank()
    {
        return Cache::rememberForever('user_rank', function() {
            $chinaNow = Carbon::now()->subDay(1);
            $yesterdayTopTenRankUser =  YesterdayScore::whereHas('user' , function ($query){
                $query->where('user_is_guest' , 0);
            })->with('user')->where('yesterday_scores.rank_date' , date('Y-m-d' , strtotime($chinaNow)))
                ->orderBy('user_score' , 'DESC')
                ->orderBy('user_id' , 'DESC')
                ->limit(10)->get();
            $userRank = collect();
            $yesterdayTopTenRankUser->each(function($item , $key) use (&$userRank){
                $user = $item->user;
                $user->user_rank_score = $item->user_score;
                $userRank->push($user);
            });
            return $userRank;
        });
    }
    public function generateYesterdayUserRank()
    {
        $yesterdayRankKey = 'user_yesterday_rank';
        $chinaNow = Carbon::now()->subDay(1);

        //清除当前缓存防止多次生成
        Redis::del($yesterdayRankKey);
        DB::table('yesterday_scores')->where('rank_date' , date('Y-m-d' , strtotime($chinaNow)))->delete();


        $post = Post::where('post_created_at' , '>=' , date('Y-m-d 00:00:00' , strtotime($chinaNow)))
            ->where('post_created_at' , '<=' , date('Y-m-d 23:59:59' , strtotime($chinaNow)))
            ->groupBy('user_id')
            ->select(DB::raw('count(*) as post_num') , 'user_id')
            ->orderBy('post_num' , 'desc')
            ->orderBy('user_id' , 'desc');
        $post->chunk(10, function ($posts) use ($yesterdayRankKey) {
            foreach ($posts as $post) {
                $postNum = $post->post_num;
                $score = $postNum*2;
                if(Redis::zrank($yesterdayRankKey , $post->user_id)==null)
                {
                    Redis::zadd($yesterdayRankKey , $score , $post->user_id);
                }else{
                    Redis::zincrby($yesterdayRankKey , $score , $post->user_id);
                }
            }
        });
        $comment = PostComment::where('comment_created_at' , '>=' , date('Y-m-d 00:00:00' , strtotime($chinaNow)))
            ->where('comment_created_at' , '<=' , date('Y-m-d 23:59:59' , strtotime($chinaNow)))
            ->groupBy('user_id')
            ->select(DB::raw('count(*) as comment_num') , 'user_id')
            ->orderBy('comment_num' , 'desc')
            ->orderBy('user_id' , 'desc');

        $comment->chunk(10, function ($comments) use ($yesterdayRankKey) {
            foreach ($comments as $comment) {
                $commentNum = $comment->comment_num;
                $score = $commentNum*3;
                if(Redis::zrank($yesterdayRankKey , $comment->user_id)==null)
                {
                    Redis::zadd($yesterdayRankKey , $score , $comment->user_id);
                }else{
                    Redis::zincrby($yesterdayRankKey , $score , $comment->user_id);
                }
            }
        });

        $like = Like::where('created_at' , '>=' , date('Y-m-d 00:00:00' , strtotime($chinaNow)))
            ->where('created_at' , '<=' , date('Y-m-d 23:59:59' , strtotime($chinaNow)))
            ->groupBy('user_id')
            ->select(DB::raw('count(*) as like_num') , 'user_id')
            ->orderBy('like_num' , 'desc')
            ->orderBy('user_id');
        $like->chunk(10, function ($likes) use ($yesterdayRankKey) {
            foreach ($likes as $like) {
                $likeNum = $like->like_num;
                $score = $likeNum;
                if(Redis::zrank($yesterdayRankKey , $like->user_id)==null)
                {
                    Redis::zadd($yesterdayRankKey , $score , $like->user_id);
                }else{
                    Redis::zincrby($yesterdayRankKey , $score , $like->user_id);
                }
            }
        });
        $i = 0;
        $rankCount = Redis::zcard($yesterdayRankKey)-1;
        do{
            $turn = $i+9;
            if($i>=$rankCount)
            {
                break;
            }
            $rankData = array();
            $userScores = Redis::zrevrange($yesterdayRankKey , $i , $turn , 'WITHSCORES');
            foreach ($userScores as $user_id=>$user_score)
            {
                array_push($rankData, array('user_id'=>$user_id , 'user_score'=>$user_score , 'rank_date'=>date('Y-m-d' , strtotime($chinaNow))));
            }
            if(!empty($rankData))
            {
                DB::table('yesterday_scores')->insert($rankData);
            }
            $i = $turn+1;
        }while(true);
        Cache::forget('user_rank');
        $this->getYesterdayUserRank();
    }

    /**
     * @inheritdoc
     */
    public function findByMany(array $ids)
    {
        $query = $this->model->query();

        if (method_exists($this->model, 'translations')) {
            $query = $query->with('translations');
        }

        return $query->whereIn("user_id", $ids)->get();
    }



    public function hiddenPosts($id=0)
    {
        if ($id === 0) {
            $id = \Auth::id();
        }
        if ($value = Redis::hget('user.'.$id.'.data', 'hiddenPosts')) {
            return json_decode($value);
        }
        $value = $this->initHiddenPosts($id);
        return $value;
    }

    public function initHiddenPosts($id=0)
    {
        if ($id === 0) {
            $id = \Auth::id();
        }
        $data = collect();
        Redis::hmset('user.'.$id.'.data', array('hiddenPosts'=>$data));
        return $data->all();
    }

    protected function cacheUserData($id)
    {
        $user = $this->model->where($this->model->getKeyName(), $id)->firstOrFail();
        $userData = [
            'hiddenPosts' => $this->hiddenPosts($user->user_id),
        ];
        Redis::hmset('user.'.$id.'.data', $userData);
        return $userData;
    }

    public function updateHiddenPosts($id, $post_uuid)
    {
        $hiddenPosts = $this->hiddenPosts($id);

        if(!in_array($post_uuid , $hiddenPosts))
        {
            array_push($hiddenPosts, $post_uuid);
        }
        // we need to make sure the cached data exists
        if (!Redis::hget('user.'.$id.'.data', 'hiddenPosts')) {
            $this->cacheUserData($id);
        }
        Redis::hset('user.'.$id.'.data', 'hiddenPosts', json_encode($hiddenPosts));
        return $hiddenPosts;
    }
}
