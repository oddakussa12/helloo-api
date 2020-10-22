<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 2019/5/19
 * Time: 18:35
 */
namespace App\Repositories\Eloquent;

use App\Jobs\RyOnline;
use Carbon\Carbon;
use App\Models\Post;
use App\Models\Like;
use App\Models\User;
use App\Models\BlockUser;
use App\Models\BlackUser;
use App\Models\BlockPost;
use App\Models\UserRegion;
use App\Models\PostComment;
use App\Models\UserTaggable;
use App\Models\YesterdayScore;
use Illuminate\Support\Facades\DB;
use App\Events\UserProfileLikeEvent;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Events\UserProfileRevokeLikeEvent;
use App\Repositories\EloquentBaseRepository;
use App\Repositories\Contracts\UserRepository;
use Dingo\Api\Exception\DeleteResourceFailedException;

class EloquentUserRepository  extends EloquentBaseRepository implements UserRepository
{
    public function __construct($model)
    {
        parent::__construct($model);

    }

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

    public function findOtherMyFollow($userId)
    {
        $followerIds = DB::table('common_follows')->where('user_id' , $userId)->where('followable_type' , User::class)->where('relation' , 'follow')->orderByDesc('id')->paginate(15 , ['followable_id'] , 'follow_page');

        $userIds = $followerIds->pluck('followable_id')->all(); //获取分页user id

        $followers = $this->findByMany($userIds);

        $authFollowers = $this->userFollow($userIds);

        $followerIds->each(function ($item, $key) use ($followers , $authFollowers) {
            $item->user = $followers->where('user_id' , $item->followable_id)->first();
        });

        $followerIds = $followerIds->filter(function ($item, $key){
            return !blank($item->user);
        });

        $followerIds->each(function ($item, $key) use ($authFollowers) {
            $item->user->user_follow_state = in_array($item->followable_id , $authFollowers);
        });

        return $followerIds;
    }

    public function findOtherFollowMe($userId)
    {
        $followerIds = DB::table('common_follows')->where('followable_id' , $userId)->where('followable_type' , User::class)->where('relation' , 'follow')->orderByDesc('id')->paginate(15 , ['user_id'] , 'follow_page');

        $userIds = $followerIds->pluck('user_id')->all(); //获取分页user id

        $followers = $this->findByMany($userIds);

        $followerIds->each(function ($item, $key) use ($followers) {
            $item->user = $followers->where('user_id' , $item->user_id)->first();
        });

        $followerIds = $followerIds->filter(function ($item, $key){
            return !blank($item->user);
        });

        $followedIds = $this->userFollow($userIds);

        $followerIds->each(function ($item, $key) use ($followedIds) {
            $item->user->user_follow_state = in_array($item->user_id , $followedIds);
        });
        return $followerIds;
    }

    public function findMyFollow($userId)
    {
        $followerIds = DB::table('common_follows')->where('user_id' , $userId)->where('followable_type' , User::class)->where('relation' , 'follow')->orderByDesc('id')->paginate(15 , ['followable_id'] , 'follow_page');

        $userIds = $followerIds->pluck('followable_id')->all(); //获取分页user id

        $followers = $this->findByMany($userIds);

        $followerIds->each(function ($item, $key) use ($followers) {
            $item->user = $followers->where('user_id' , $item->followable_id)->first();
            $item->user->user_follow_state = true;
        });

        $followerIds = $followerIds->filter(function ($item, $key){
            return !blank($item->user);
        });

        return $followerIds;
    }

    public function findFollowMe($userId)
    {
        $followerIds = DB::table('common_follows')->where('followable_id' , $userId)->where('followable_type' , User::class)->where('relation' , 'follow')->orderByDesc('id')->paginate(15 , ['user_id'] , 'follow_page');

        $userIds = $followerIds->pluck('user_id')->all(); //获取分页user id

        $followers = $this->findByMany($userIds);

        $followerIds->each(function ($item, $key) use ($followers) {
            $item->user = $followers->where('user_id' , $item->user_id)->first();
        });

        $followerIds = $followerIds->filter(function ($item, $key){
            return !blank($item->user);
        });

        $followedIds = $this->userFollow($userIds);//重新获取当前登录用户信息

        $followerIds->each(function ($item, $key) use ($followedIds) {
            $item->user->user_follow_state = in_array($item->user_id , $followedIds);
        });
        return $followerIds;
    }

    public function findByWhere($where)
    {
        return $this->model->where($where)->first();
    }

    public function getUserRank()
    {
        $rankTopTenUser = $this->getYesterdayUserRank();

        $userIds = $rankTopTenUser->pluck('user_id')->all();

        $followers = $this->userFollow($userIds);

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

    public function unblockUser($userId)
    {
        if (auth()->check()) {
            $authUser = auth()->id();
            if ($authUser != $userId) {
                $this->removeHiddenUsers($authUser, $userId);
            }
        }
    }

    public function removeHiddenUsers($id, $user_id)
    {
        $userHiddenUsersKey = $this->hiddenUsersMemKey($id);
        if(Redis::sismember($userHiddenUsersKey , $user_id))
        {
            Redis::srem($userHiddenUsersKey , $user_id);
            BlockUser::where('user_id' , $id)->where('blocked_user_id' , $user_id)->update(
                array('is_deleted'=>1)
            );
        }
    }

    public function blockUser($userId)
    {
        if (auth()->check()) {
            // 不屏蔽自己
            $authUser = auth()->id();
            if ($authUser != $userId) {
                $this->updateHiddenUsers($authUser, $userId);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function findByMany(array $ids)
    {
        $query = $this->model->query();
        return $query->whereIn("user_id", $ids)->get();
    }

    /**
     * @param $id
     * @return string
     * 屏蔽用户 redis key
     */
    public function hiddenUsersMemKey($id)
    {
        return 'user.'.$id.'.hidden.users';
    }

    /**
     * @param $id
     * @param $user_id
     * @return mixed
     * 添加屏蔽用户至redis
     */
    public function updateHiddenUsers($id, $user_id)
    {
        $userHiddenUsersKey = $this->hiddenUsersMemKey($id);
        if(!Redis::sismember($userHiddenUsersKey , $user_id))
        {
            Redis::sadd($userHiddenUsersKey , $user_id);
            BlockUser::create(array(
                'user_id'=>$id,
                'blocked_user_id'=>$user_id,
            ));
        }
//        return Redis::smembers($userHiddenUsersKey);
        /*$hiddenUsers = $this->hiddenUsers($id);

        if(!in_array($user_id , $hiddenUsers))
        {
            array_push($hiddenUsers, $user_id);
        }
        Redis::hset('user.'.$id.'.data', 'hiddenUsers', json_encode($hiddenUsers));
        return $hiddenUsers;*/
    }

    public function hiddenUsers($id=0)
    {
        if ($id === 0) {
            $id = \Auth::id();
        }
        return Redis::sMembers($this->hiddenUsersMemKey($id));

        /*if ($value = Redis::hget('user.'.$id.'.data', 'hiddenUsers')) {
            return json_decode($value , true);
        }
        return $this->initHiddenUsers($id);
        */
    }

    public function hiddenPosts($id=0)
    {
        if ($id === 0) {
            $id = \Auth::id();
        }
        return Redis::sMembers($this->hiddenPostsMemKey($id));
        /*
        if ($value = Redis::hget('user.'.$id.'.data', 'hiddenPosts')) {
            return json_decode($value , true);
        }
        $value = $this->initHiddenPosts($id);
        return $value;*/
    }

    public function initHiddenUsers($id=0)
    {
        if ($id === 0) {
            $id = \Auth::id();
        }
        $data = collect();
        Redis::hmset('user.'.$id.'.data', array('hiddenUsers'=>$data));
        return $data->all();
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

    protected function randRyOnlineUser()
    {
        $key = 'ry_user_online_status';
        return Redis::srandmember($key);
    }

    public function randDiffRyOnlineUserByHobby()
    {
        $selfUser = intval(request()->input('self'));
        if($selfUser>0)
        {
            $where = '';
            $country_code = config('countries');
            $usedUser = (array)request()->input('used' , array());
            $usedUser = array_slice($usedUser , 0 , 29);
            array_push($usedUser , $selfUser);
            $usedUser = array_unique($usedUser);
            $usedUser = array_filter($usedUser , function($v){
                return !empty($v);
            });
            $usedUser = array_merge($usedUser , [35525, 219367, 28583, 28527, 69684, 97623, 28761]);
            $userIds = join(',' , $usedUser);
            if(!blank($userIds))
            {
                $where .= " AND u1.user_id not in ({$userIds})";
            }
            $hobby = strval(request()->input('hobby'));
            if(!blank($hobby)&&in_array($hobby , array('kpop' , 'anime' , 'sad' , 'music' , 'games' , 'sports')))
            {
                $where .= " AND u1.{$hobby} = 1";
            }
            $onlineSql = <<<DOC
SELECT u1.user_id,u1.user_name,u1.user_nick_name,u1.user_avatar,u1.user_country_id FROM `f_ry_online_users` AS u1 JOIN (SELECT ROUND(RAND() * ((SELECT MAX(`user_id`) FROM `f_ry_online_users`)-(SELECT MIN(`user_id`) FROM `f_ry_online_users`))+(SELECT MIN(`user_id`) FROM `f_ry_online_users`)) AS user_id) AS u2 WHERE u1.user_id >= u2.user_id {$where} ORDER BY u1.user_id LIMIT 1;
DOC;
            $user = collect(\DB::select($onlineSql))->first();
            if(blank($user))
            {
                return $this->findOrFail($this->randRyOnlineUser());
            }
            $country_id = intval($user->user_country_id-1);
            $user->user_level = 0;
            $user->user_country = strtolower($country_code[$country_id]);
            $user->user_avatar_link = config('common.qnUploadDomain.avatar_domain').$user->user_avatar.'?imageView2/0/w/50/h/50/interlace/1|imageslim';
            $user->user_continent = getContinentByCountry($user->user_country);
            return $user;
        }else{
            return $this->findOrFail($this->randRyOnlineUser());
        }
    }

    public function randDiffRyOnlineUserV2()
    {
        $selfUser = intval(request()->input('self'));
        if($selfUser>0)
        {
            $where = '';
            $country_code = config('countries');
            $user_gender = intval(request()->input('user_gender' , 2));
            $country = strval(request()->input('country' , 0));
            $country_op = intval(request()->input('country_op' , 0));
            $user_country = array_search(strtoupper($country) , config('countries'));
            $user_country_id = $user_country===false?0:$user_country+1;
//            $operator = $country_op===0?'!=':'=';
            $usedUser = (array)request()->input('used' , array());
            $userAge = strval(request()->input('user_age' , "0,0"));
            $usedUser = array_slice($usedUser , 0 , 29);
            array_push($usedUser , $selfUser);
            $usedUser = array_unique($usedUser);
            $usedUser = array_filter($usedUser , function($v){
                return !empty($v);
            });
            $usedUser = array_merge($usedUser , [35525, 219367, 28583, 28527, 69684, 97623, 28761]);
            $userIds = join(',' , $usedUser);
            if(in_array($user_gender , array(0 , 1)))
            {
                if(blank($where))
                {
                    $where .= "u1.user_gender = {$user_gender}";
                }else{
                    $where .= " AND u1.user_gender = {$user_gender}";
                }
            }
            if(in_array($country_op , array(0 , 1)))
            {
                $operator = $country_op===0?'!=':'=';
                $where .= "u1.user_country_id {$operator} {$user_country_id}";
            }
            if(!blank($userIds))
            {
                if(blank($where))
                {
                    $where .= "u1.user_id not in ({$userIds})";
                }else{
                    $where .= " AND u1.user_id not in ({$userIds})";
                }
            }
            if($userAge!='0,0')
            {
                $userAge = $userAge.",";
                list ($userStartAge , $userEndAge) = explode(',' , $userAge);
                $userStartAge = intval($userStartAge);
                $userEndAge = intval($userEndAge);
                if($userStartAge>0&&$userStartAge<$userEndAge&&$userEndAge<=100)
                {
                    if(blank($where))
                    {
                        $where .= "u1.user_age BETWEEN {$userStartAge} AND {$userEndAge}";
                    }else{
                        $where .= " AND u1.user_age BETWEEN {$userStartAge} AND {$userEndAge}";
                    }
                }
            }
            !blank($where)&&$where = $where." AND";
            $onlineSql = <<<DOC
SELECT u1.user_id,u1.user_name,u1.user_nick_name,u1.user_avatar,u1.user_country_id FROM `f_ry_online_users` AS u1 JOIN (SELECT ROUND(RAND() * ((SELECT MAX(`user_id`) FROM `f_ry_online_users`)-(SELECT MIN(`user_id`) FROM `f_ry_online_users`))+(SELECT MIN(`user_id`) FROM `f_ry_online_users`)) AS user_id) AS u2 WHERE {$where} u1.user_id >= u2.user_id ORDER BY u1.user_id LIMIT 1;
DOC;
            $user = collect(\DB::select($onlineSql))->first();
            if(blank($user))
            {
                return $this->findOrFail($this->randRyOnlineUser());
            }
            $country_id = intval($user->user_country_id-1);
            $user->user_level = 0;
            $user->user_country = strtolower($country_code[$country_id]);
            $user->user_avatar_link = config('common.qnUploadDomain.avatar_domain').$user->user_avatar.'?imageView2/0/w/50/h/50/interlace/1|imageslim';
            $user->user_continent = getContinentByCountry($user->user_country);
            return $user;
        }else{
            return $this->findOrFail($this->randRyOnlineUser());
        }
    }

    public function randDiffRyOnlineUser()
    {
        $key = 'ry_user_online_status';
        $selfUser = intval(request()->input('self'));
        if($selfUser>0)
        {
            $tmpUsedUserKey = 'ry_tmp_used_user_'.$selfUser;
            $diffUsedUserKey = 'ry_diff_used_user_'.$selfUser;
            $usedUser = request()->input('used' , array());
            $usedUser = getType($usedUser)=='array'?$usedUser:array();
            $usedUser = array_slice($usedUser , 0 , 2);
            array_push($usedUser , $selfUser);
            $usedUser = array_unique($usedUser);
            if(!empty($usedUser))
            {
                Redis::sadd($tmpUsedUserKey , $usedUser);
            }
            Redis::sdiffstore($diffUsedUserKey , array($key , $tmpUsedUserKey));

            $user =  Redis::srandmember($diffUsedUserKey);

            Redis::del($diffUsedUserKey);
            Redis::del($tmpUsedUserKey);
            if(intval($user)<=0)
            {
                $randUsersFile = 'randUsers/users.json';
                if(Storage::exists($randUsersFile))
                {
                    $randUsers = \json_decode(Storage::get($randUsersFile) , true);
                    if(getType($randUsers)=='array')
                    {
                        $user = array_random(array_unique(array_diff($randUsers , $usedUser)));
                    }
                }
            }
            return $user;
        }else{
            return $this->randRyOnlineUser();
        }
    }

    public function updateUserOnlineState($users)
    {
//        $currentMinute = date('i');
//        $index = floor($currentMinute/5);
        $lastActivityTime = 'ry_user_last_activity_time';
        $key = 'ry_user_online_status';
        $bitKey = 'ry_user_online_status_bit';
//        $dynamicKey = config('redis-key.user.ry_update_online_status')."_".strval($index);
        $users = \array_filter($users , function($v , $k){
            return in_array($v , array(0 , 1 , 2))&&!empty($k);
        } , ARRAY_FILTER_USE_BOTH );
        $offlineUsers = array_where($users, function ($value, $key) {
            return intval($value)>0;
        });
        $onlineUsers = array_where($users, function ($value, $key) {
            return intval($value)===0;
        });
        !blank($onlineUsers)&&Redis::sadd($key , array_keys($onlineUsers));
        !blank($offlineUsers)&&Redis::srem($key , array_keys($offlineUsers));
        $time = time();
        !blank($users)&&array_walk($users , function ($v , $k) use ($bitKey , $lastActivityTime , $time){
            $v = intval($v)>0?0:1;
            Redis::setBit($bitKey , intval($k) , intval($v));
//            Redis::zadd($dynamicKey , intval($v) , intval($k));
            Redis::zadd($lastActivityTime , $time , intval($k));
        });
//        Redis::expire($dynamicKey,900);
        RyOnline::dispatch(array(
            'offlineUsers'=>$offlineUsers,
            'onlineUsers'=>$onlineUsers,
        ))->onConnection('sqs')->onQueue('ry_user_online');
    }

    public function isOnline($id)
    {
        $bitKey = 'ry_user_online_state_bit';
        $bitKey = 'ry_user_online_status_bit';
        $statue = Redis::getBit($bitKey , $id);
        return (bool)intval($statue);
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

    /**
     * @param $id
     * @return string
     * 屏蔽帖子 redis key
     */
    public function hiddenPostsMemKey($id)
    {
        return 'user.'.$id.'.hidden.posts';
    }

    /**
     * @param $id
     * @param $post_uuid
     * @return mixed
     * 添加屏蔽帖子至redis
     */
    public function updateHiddenPosts($id, $post_uuid)
    {
        $userHiddenPostsKey = $this->hiddenPostsMemKey($id);
        if(!Redis::sismember($userHiddenPostsKey , $post_uuid))
        {
            Redis::sadd($userHiddenPostsKey , $post_uuid);
            BlockPost::create(
                array(
                    'user_id'=>$id,
                    'blocked_post_uuid'=>$post_uuid,
                )
            );
        }
//        return Redis::sMembers($userHiddenPostsKey);

        /*$hiddenPosts = $this->hiddenPosts($id);
        if (!in_array($post_uuid , $hiddenPosts)) {
            array_push($hiddenPosts, $post_uuid);
        }*/

        // we need to make sure the cached data exists
        /*if (!Redis::hget('user.'.$id.'.data', 'hiddenPosts')) {
            $this->cacheUserData($id);
        }
        Redis::hset('user.'.$id.'.data', 'hiddenPosts', json_encode($hiddenPosts));*/
        //return $hiddenPosts;
    }

    public function randFollow()
    {
//        $topTwoHundredFollower = \DB::select("SELECT
//	`f_users`.`user_id`, count(`f_common_follows`.`user_id`) AS `num`
//FROM
//	`f_users`,
//	`f_common_follows`
//WHERE
//	`f_users`.`user_id` = `f_common_follows`.`followable_id`
//GROUP BY
//	`followable_id`
//ORDER BY
//	`num` DESC
//LIMIT 200");

        $followers = $this->generateFollower();
        $follower = collect($followers)->random();
        $follower->follow(array('63915'));
//        $topTwoHundredFollower = collect($topTwoHundredFollower)->chunk(10);
//        collect($topTwoHundredFollower)->each(function($users , $key)use($followers){
//            $follower = collect($followers)->random();
//            $users = $users->pluck('user_id')->all();
//            $follower->follow($users);
//        });
    }

    public function isDeletedUser($name)
    {
        $deletedUsers = $this->getDeletedUsers();
        throw_if($deletedUsers->has($name), new DeleteResourceFailedException('Your account has been deleted!'));
        throw_if($deletedUsers->flip()->has($name), new DeleteResourceFailedException('Your account has been deleted!'));
    }

    public function isBlackUser($user_id)
    {
       return BlackUser::where('user_id',$user_id)->where('is_delete', 0)->first();
    }

    public function getDeletedUsers()
    {

    }

    public function generateFollower()
    {
        $faker = [
        ];
        return $this->model->inRandomOrder()->whereIn('user_id' , $faker)->take(10)->get();
    }

    public function findByLikeName($name)
    {
        return $this->model->where('user_name' , 'like' , "%{$name}%")->orderByRaw("REPLACE(user_name,'{$name}','')")->select('user_id' , 'user_name' ,'user_nick_name' , 'user_level' , 'user_avatar' , 'user_score' , 'user_country_id' , 'user_is_guest')->limit(5)->get();
    }

    public function findByLikeNickName($name)
    {
        return $this->model->where('user_nick_name' , 'like' , "%{$name}%")->orderByRaw("REPLACE(user_nick_name,'{$name}','')")->select('user_id' , 'user_name' , 'user_nick_name' , 'user_level' , 'user_avatar' , 'user_score' , 'user_country_id' , 'user_is_guest')->limit(5)->get();
    }

    public function profileLike($id)
    {
        $likeUser = auth()->user();
        $likeUserId = $likeUser->user_id;
        $userProfileLikeKey = 'user.'.$id.'.profile.like';
        if(Redis::zrank($userProfileLikeKey , $likeUserId)===null)
        {
            $user = $this->findOrFail($id);
            $like = $likeUser->profileLike()->updateOrCreate(array('profile_user_id'=>$user->getKey()));
            event(new UserProfileLikeEvent($likeUser , $user , $like));
        }
    }

    public function profileRevokeLike($id)
    {
        $likeUser = auth()->user();
        $likeUserId = $likeUser->user_id;
        $userProfileLikeKey = 'user.'.$id.'.profile.like';
        if(Redis::zrank($userProfileLikeKey , $likeUserId)!==null)
        {
            $user = $this->findOrFail($id);
            $likeUser->profileLike()->delete();
            event(new UserProfileRevokeLikeEvent($likeUser , $user));
        }
    }

    public function attachTags(Model $user  , $tag_slug)
    {
        $userTags = config('user-tag');
        $tag_slug = array_filter($tag_slug ,function($v) use ($userTags){
            return in_array($v , $userTags);
        });
        $tagIds = array_map(function($v) use ($userTags){
            return array_search($v , $userTags);
        } , $tag_slug);
        $tagIds = array_filter($tagIds ,function($v){
            return is_int($v);
        });
        $taggable_id = $user->getKey();
        $taggable_type = $user->getMorphClass();
        $userTaggable = UserTaggable::where('taggable_id' , $taggable_id)->where('taggable_type' , $taggable_type)->pluck('tag_id')->all();
        $newTagIds = array_diff($tagIds , $userTaggable);
        $removeTagIds = array_diff($userTaggable , $tagIds);
        $newTags = array_map(function($v) use ($taggable_id , $taggable_type){
            return array('taggable_id'=>$taggable_id , 'tag_id'=>$v , 'taggable_type'=>$taggable_type);
        } , $newTagIds);
        !blank($newTags)&&UserTaggable::insert($newTags);
        !blank($removeTagIds)&&UserTaggable::where('taggable_id' , $taggable_id)->whereIn('tag_id' , $removeTagIds)->delete();
//        $tags = UserTag::whereIn('tag_slug' , $tag_slug)->select('tag_id' , 'tag_slug')->get();
//        $tags_id = $tags->pluck('tag_id')->toArray();
//        $tags_id = array_filter($tags_id ,function($v){
//            return is_int($v);
//        });
//        $user->tags()->sync($tags_id);
    }

    public function referFriend()
    {
        $userIds = $this->randReferFriend();
        $referFriends = array();
        $userIds = array_unique(array_merge($userIds , $referFriends));
        $userIds = array_diff($userIds , array(auth()->id()));
        $query = $this->model->query();
        return $query->whereIn("user_id", $userIds)->get();
    }

    public function randReferFriend()
    {
        $key = 'ry_user_online_status';
        return Redis::srandmember($key , config('common.refer_friend_num'));
    }

    public function attachRegions(Model $user  , $region_slug)
    {
        $userRegions = config('user-region');
        $region_slug = array_filter($region_slug ,function($v) use ($userRegions){
            return in_array($v , $userRegions);
        });
        $regionIds = array_map(function($v) use ($userRegions){
            return array_search($v , $userRegions);
        } , $region_slug);
        $regionIds = array_filter($regionIds ,function($v){
            return is_int($v);
        });
        $userId = $user->getKey();
        $userRegions = UserRegion::where('user_id' , $userId)->pluck('region_id')->all();
        $newRegionIds = array_diff($regionIds , $userRegions);
        $removeRegionIds = array_diff($userRegions , $regionIds);
        $newRegions = array_map(function($v) use ($userId){
            return array('user_id'=>$userId , 'region_id'=>$v);
        } , $newRegionIds);
        !blank($newRegions)&&UserRegion::insert($newRegions);
        !blank($removeRegionIds)&&UserRegion::where('user_id' , $userId)->whereIn('region_id' , $removeRegionIds)->delete();
//
//        $regions = Region::whereIn('region_slug' , $region_slug)->select('region_id' , 'region_slug')->get();
//        $regions_id = $regions->pluck('region_id')->toArray();
//        $regions_id = array_filter($regions_id ,function($v){
//            return is_int($v);
//        });
//        $user->regions()->sync($regions_id);
    }

    public function onlineUsersCount()
    {
        $key = 'ry_user_online_status';
        return Redis::scard($key);
    }

    public function filter()
    {
        $country_code = config('countries');
        $users = DB::table('ry_online_users');
        $user_gender = intval(request()->input('user_gender' , 2));
        $country = strval(request()->input('country' , 0));
        $country_op = intval(request()->input('country_op' , 2));
        $user_country = array_search(strtoupper($country) , $country_code);
        $userAge = strval(request()->input('user_age' , "0,0"));
        $user_country_id = $user_country===false?0:$user_country+1;
        if($country_op==1)
        {
            !empty($user_country_id)&&$users = $users->where('user_country_id' , $user_country_id);
        }elseif ($country_op==0)
        {
            !empty($user_country_id)&&$users = $users->where('user_country_id' , '!='  , $user_country_id);
        }
        if(in_array($user_gender , array(0 , 1)))
        {
            $users = $users->where('user_gender' , $user_gender);
        }

        if($userAge!='0,0')
        {
            $userAge = $userAge.",";
            list ($userStartAge , $userEndAge) = explode(',' , $userAge);
            $userStartAge = intval($userStartAge);
            $userEndAge = intval($userEndAge);
            if($userStartAge>0&&$userStartAge<$userEndAge&&$userEndAge<=100)
            {
                $users = $users->whereBetween('user_age' , array($userStartAge , $userEndAge));
            }
        }
        $userIds = $users->inRandomOrder()->take(10)->get()->pluck('user_id')->toArray();
        return $this->findByMany($userIds);
    }

    public function planet()
    {
        $num = intval(request()->input('num' , 20));
        $num = $num<10||$num>30?10:$num;
        $key = 'ry_user_online_status';
        return Redis::srandmember($key , $num);
    }

    public function userFollow($userIds)
    {
        if(auth()->check()&&!empty($userIds))
        {
            return \DB::table('common_follows')->where('user_id' , auth()->id())->where('followable_type' , "App\Models\User")->where('relation' , "follow")->whereIn('followable_id' , $userIds)->pluck('followable_id')->all();
        }
        return array();
    }
}
