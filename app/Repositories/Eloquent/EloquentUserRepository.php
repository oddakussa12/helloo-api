<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 2019/5/19
 * Time: 18:35
 */
namespace App\Repositories\Eloquent;


use Carbon\Carbon;
use App\Models\User;
use App\Jobs\RyOnline;
use App\Models\BlockUser;
use App\Models\BlackUser;
use App\Models\BlockPost;
use App\Models\YesterdayScore;
use Illuminate\Support\Facades\DB;
use App\Events\UserProfileLikeEvent;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
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


    public function update($model, $data)
    {
        $user = parent::update($model, $data);
        $key = "helloo:account:service:account:".$model->getKey();
        $genderSortSetKey = 'helloo:account:service:account-gender-sort-set';
        Redis::del($key);
        isset($data['user_gender'])&&Redis::zadd($genderSortSetKey , intval($data['user_gender']) , $model->getKey());
        return $user;
    }

    public function store($data)
    {
        return $this->model->create($data);
    }

    public function findByUserId($userId)
    {
        $key = "helloo:account:service:account:".$userId;
        $user = Redis::hgetall($key);
        if(blank($user))
        {
            $user = $this->model->find($userId);
            if(!blank($user))
            {
                $cache = collect($user)->toArray();
                Redis::hmset($key , $cache);
                Redis::expire($key , 60*60*24*30);
            }
        }else{
            $user = collect($user);
        }
        return $user;
    }

    public function findOrFail($userId)
    {
        return $this->model->findOrFail($userId);
    }

    public function findOtherMyFollow($userId)
    {
        $followerIds = DB::table('common_follows')->where('user_id', $userId)->where('followable_type', User::class)->where('relation', 'follow')->orderByDesc('id')->paginate(15, ['followable_id'], 'follow_page');

        $userIds = $followerIds->pluck('followable_id')->all(); //获取分页user id

        $followers = $this->findByMany($userIds);

        $authFollowers = $this->userFollow($userIds);

        $followerIds->each(function ($item, $key) use ($followers, $authFollowers) {
            $item->user = $followers->where('user_id', $item->followable_id)->first();
        });

        $followerIds = $followerIds->filter(function ($item, $key) {
            return !blank($item->user);
        });

        $followerIds->each(function ($item, $key) use ($authFollowers) {
            $item->user->user_follow_state = in_array($item->followable_id, $authFollowers);
        });

        return $followerIds;
    }

    public function findOtherFollowMe($userId)
    {
        $followerIds = DB::table('common_follows')->where('followable_id', $userId)->where('followable_type', User::class)->where('relation', 'follow')->orderByDesc('id')->paginate(15, ['user_id'], 'follow_page');

        $userIds = $followerIds->pluck('user_id')->all(); //获取分页user id

        $followers = $this->findByMany($userIds);

        $followerIds->each(function ($item, $key) use ($followers) {
            $item->user = $followers->where('user_id', $item->user_id)->first();
        });

        $followerIds = $followerIds->filter(function ($item, $key) {
            return !blank($item->user);
        });

        $followedIds = $this->userFollow($userIds);

        $followerIds->each(function ($item, $key) use ($followedIds) {
            $item->user->user_follow_state = in_array($item->user_id, $followedIds);
        });
        return $followerIds;
    }

    public function findMyFollow($userId)
    {
        $followerIds = DB::table('common_follows')->where('user_id', $userId)->where('followable_type', User::class)->where('relation', 'follow')->orderByDesc('id')->paginate(15, ['followable_id'], 'follow_page');

        $userIds = $followerIds->pluck('followable_id')->all(); //获取分页user id

        $followers = $this->findByMany($userIds);

        $followerIds->each(function ($item, $key) use ($followers) {
            $item->user = $followers->where('user_id', $item->followable_id)->first();
            $item->user->user_follow_state = true;
        });

        $followerIds = $followerIds->filter(function ($item, $key) {
            return !blank($item->user);
        });

        return $followerIds;
    }

    public function findFollowMe($userId)
    {
        $followerIds = DB::table('common_follows')->where('followable_id', $userId)->where('followable_type', User::class)->where('relation', 'follow')->orderByDesc('id')->paginate(15, ['user_id'], 'follow_page');

        $userIds = $followerIds->pluck('user_id')->all(); //获取分页user id

        $followers = $this->findByMany($userIds);

        $followerIds->each(function ($item, $key) use ($followers) {
            $item->user = $followers->where('user_id', $item->user_id)->first();
        });

        $followerIds = $followerIds->filter(function ($item, $key) {
            return !blank($item->user);
        });

        $followedIds = $this->userFollow($userIds);//重新获取当前登录用户信息

        $followerIds->each(function ($item, $key) use ($followedIds) {
            $item->user->user_follow_state = in_array($item->user_id, $followedIds);
        });
        return $followerIds;
    }

    public function findByWhere($where)
    {
        return $this->model->where($where)->first();
    }



    public function getYesterdayUserRank()
    {
        return Cache::rememberForever('user_rank', function () {
            $chinaNow = Carbon::now()->subDay(1);
            $yesterdayTopTenRankUser = YesterdayScore::whereHas('user', function ($query) {
                $query->where('user_is_guest', 0);
            })->with('user')->where('yesterday_scores.rank_date', date('Y-m-d', strtotime($chinaNow)))
                ->orderBy('user_score', 'DESC')
                ->orderBy('user_id', 'DESC')
                ->limit(10)->get();
            $userRank = collect();
            $yesterdayTopTenRankUser->each(function ($item, $key) use (&$userRank) {
                $user = $item->user;
                $user->user_rank_score = $item->user_score;
                $userRank->push($user);
            });
            return $userRank;
        });
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
        if (Redis::sismember($userHiddenUsersKey, $user_id)) {
            Redis::srem($userHiddenUsersKey, $user_id);
            BlockUser::where('user_id', $id)->where('blocked_user_id', $user_id)->update(
                array('is_deleted' => 1)
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
        return 'user.' . $id . '.hidden.users';
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
        if (!Redis::sismember($userHiddenUsersKey, $user_id)) {
            Redis::sadd($userHiddenUsersKey, $user_id);
            BlockUser::create(array(
                'user_id' => $id,
                'blocked_user_id' => $user_id,
            ));
        }
    }

    public function hiddenUsers($id = 0)
    {
        if ($id === 0) {
            $id = \Auth::id();
        }
        return Redis::sMembers($this->hiddenUsersMemKey($id));
    }

    public function hiddenPosts($id = 0)
    {
        if ($id === 0) {
            $id = \Auth::id();
        }
        return Redis::sMembers($this->hiddenPostsMemKey($id));
    }

    protected function randRyOnlineUser()
    {
        $key = 'helloo:account:service:account-random-im-set';
        return Redis::srandmember($key);
    }

    protected function randRyVoiceUser()
    {
        $setKey = 'helloo:account:service:account-random-voice-set';
        return Redis::spop($setKey);
    }

    protected function randRyVideoUser()
    {
        $setKey = 'helloo:account:service:account-random-video-set';
        return Redis::spop($setKey);
    }


    public function randomIm($self)
    {
        $key = 'helloo:account:service:account-random-im-set';
        $maleKey = 'helloo:account:service:account-random-male-im-set';
        $femaleKey = 'helloo:account:service:account-random-female-im-set';
        $genderSortSetKey = 'helloo:account:service:account-gender-sort-set';
        $gender = Redis::zscore($genderSortSetKey , $self);
        if($gender!==null)
        {
            if($gender==0)
            {
                Redis::sadd($femaleKey , $self);
            }elseif($gender==1){
                Redis::sadd($maleKey , $self);
            }
        }
        $turn = 0;
        $s = 600000;
        while (true)
        {
            usleep($s);
            if($turn>5)
            {
                Redis::sadd($key , $self);
                $userId = 0;
                break;
            }
            $userId = $this->randRyOnlineUser();
            if(!empty($userId)&&$userId!=$self)
            {
                break;
            }
            $s = $s-50000;
            $turn++;
        }
        return $userId;
    }

    public function randomVideo($self)
    {
        $flag = false;
        $imKey = 'helloo:account:service:account-random-im-set';
        $setKey = 'helloo:account:service:account-random-video-set';
        $sortSetKey = 'helloo:account:service:account-random-video-sort-set';
        $cancelSetKey = 'helloo:account:service:account-cancel-video-random:'.$self;
        Redis::del($cancelSetKey);
        Redis::zadd($sortSetKey , time() , $self);
        Redis::sadd($imKey , $self);
        $turn = 1;
        $s = 600000;
        $userId = 0;
        while (true)
        {
            usleep($s);
            if($turn>10)
            {
                $userId = 0;
                break;
            }
            if(!$this->isCancelVideoRandom($self))
            {
                $userId = intval($this->randRyVideoUser());
                $userId = $this->isCancelVideoRandom($userId)?0:$userId;
            }
            if(!empty($userId)&&$userId!=$self)
            {
                break;
            }

            $s = $s-50000;
            $turn++;
        }
        if($userId==0)
        {
            !$this->isCancelVideoRandom($self)&&Redis::sadd($setKey , $self);
            $roomId = md5($self);
            return array('flag'=>$flag , 'roomId'=>$roomId);
        }else{
            if($this->isCancelVideoRandom($self))
            {
                $roomId = md5($self);
                return array('flag'=>$flag , 'roomId'=>$roomId);
            }else{
                $flag = true;
                $roomId = md5($userId);
                $data = array('userId'=>$userId , 'flag'=>$flag , 'roomId'=>$roomId);
                if($this->official($userId))
                {
                    $locale = locale();
                    if($locale=='id')
                    {
                        $text = "Selamat ! Anda telah dipertemukan dengan Artis Helloo";
                    }elseif($locale=='zh-CN')
                    {
                        $text = '恭喜您匹配到了Helloo Star.';
                    }else{
                        $text = 'Bingo！🎉 You have been matched with a Helloo Star.';
                    }
                    $data['official'] = $text;
                }
                return $data;
            }
        }
    }

    public function randomVoice($self)
    {
        $flag = false;
        $imKey = 'helloo:account:service:account-random-im-set';
        $setKey = 'helloo:account:service:account-random-voice-set';
        $sortSetKey = 'helloo:account:service:account-random-voice-sort-set';
        $cancelSetKey = 'helloo:account:service:account-cancel-voice-random:'.$self;
        Redis::del($cancelSetKey);
        Redis::zadd($sortSetKey , time() , $self);
        Redis::sadd($imKey , $self);
        $turn = 1;
        $s = 600000;
        $userId = 0;
        while (true)
        {
            usleep($s);
            if($turn>10)
            {
                $userId = 0;
                break;
            }
            if(!$this->isCancelVoiceRandom($self))
            {
                $userId = intval($this->randRyVoiceUser());
                $userId = $this->isCancelVoiceRandom($userId)?0:$userId;
            }
            if(!empty($userId)&&$userId!=$self)
            {
                break;
            }
            $s = $s-50000;
            $turn++;
        }
        if($userId==0)
        {
            !$this->isCancelVoiceRandom($self)&&Redis::sadd($setKey , $self);
            $roomId = md5($self);
            return array('flag'=>$flag , 'roomId'=>$roomId);
        }else{
            if($this->isCancelVoiceRandom($self))
            {
                $roomId = md5($self);
                return array('flag'=>$flag , 'roomId'=>$roomId);
            }else{
                $flag = true;
                $roomId = md5($userId);
                return array('userId'=>$userId , 'flag'=>$flag , 'roomId'=>$roomId);
            }
        }
    }

    public function removeVoice()
    {
        $self = auth()->id();
        $cancelSetKey = 'helloo:account:service:account-cancel-voice-random:'.$self;
        Redis::set($cancelSetKey, 1, "nx", "ex", 6);
        $setKey = 'helloo:account:service:account-random-voice-set';
        Redis::srem($setKey , $self);
    }

    public function removeVideo()
    {
        $self = auth()->id();
        $cancelSetKey = 'helloo:account:service:account-cancel-video-random:'.$self;
        Redis::set($cancelSetKey, 1, "nx", "ex", 6);
        $setKey = 'helloo:account:service:account-random-video-set';
        Redis::srem($setKey , $self);
    }

    public function isCancelVideoRandom($self)
    {
        $cancelSetKey = 'helloo:account:service:account-cancel-video-random:'.$self;
        return Redis::exists($cancelSetKey);
    }

    public function isCancelVoiceRandom($self)
    {
        $cancelSetKey = 'helloo:account:service:account-cancel-voice-random:'.$self;
        return Redis::exists($cancelSetKey);
    }

    public function updateUserOnlineState($users)
    {
        RyOnline::dispatch($users)->onConnection('sqs-fifo')->onQueue('helloo_ry_user_online.fifo');
    }

    public function isOnline($id)
    {
        $bitKey = 'helloo:account:service:account-online-status-bit';
        $statue = Redis::getBit($bitKey, $id);
        return (bool)intval($statue);
    }


    /**
     * @param $id
     * @return string
     * 屏蔽帖子 redis key
     */
    public function hiddenPostsMemKey($id)
    {
        return 'user.' . $id . '.hidden.posts';
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
        if (!Redis::sismember($userHiddenPostsKey, $post_uuid)) {
            Redis::sadd($userHiddenPostsKey, $post_uuid);
            BlockPost::create(
                array(
                    'user_id' => $id,
                    'blocked_post_uuid' => $post_uuid,
                )
            );
        }
    }


    public function isDeletedUser($name)
    {
        $deletedUsers = $this->getDeletedUsers();
        throw_if($deletedUsers->has($name), new DeleteResourceFailedException('Your account has been deleted!'));
        throw_if($deletedUsers->flip()->has($name), new DeleteResourceFailedException('Your account has been deleted!'));
    }

    public function isBlackUser($user_id)
    {
        return BlackUser::where('user_id', $user_id)->where('is_delete', 0)->first();
    }

    public function getDeletedUsers()
    {

    }

    public function generateFollower()
    {
        $faker = [
        ];
        return $this->model->inRandomOrder()->whereIn('user_id', $faker)->take(10)->get();
    }

    public function findByLikeName($name)
    {
        return $this->model->where('user_name', 'like', "%{$name}%")->orderByRaw("REPLACE(user_name,'{$name}','')")->select('user_id', 'user_name', 'user_nick_name', 'user_level', 'user_avatar', 'user_score', 'user_country_id', 'user_is_guest')->limit(5)->get();
    }

    public function findByLikeNickName($name)
    {
        return $this->model->where('user_nick_name', 'like', "%{$name}%")->orderByRaw("REPLACE(user_nick_name,'{$name}','')")->select('user_id', 'user_name', 'user_nick_name', 'user_level', 'user_avatar', 'user_score', 'user_country_id', 'user_is_guest')->limit(5)->get();
    }

    public function like($userId)
    {
        $likedKey = 'helloo:account:service:account-liked-num';
//        $likeKey = 'helloo:account:service:account-like-num';
        $authId = auth()->id();
        $like = DB::table('likes')->where('user_id' , $authId)->where('liked_id' , $userId)->first();
        if(empty($like))
        {
            $liked = DB::table('users')->where('user_id' , $userId)->first();
            if(empty($liked))
            {
                DB::table('likes')->insert(
                    array('user_id'=>$authId , 'liked_id'=>$userId)
                );
                $likeNum = DB::table('likes')->where('liked_id' , $userId)->count();
                Redis::zadd($likedKey , $likeNum , $userId);
            }
        }
    }

    public function profileLike($id)
    {
        $likeUser = auth()->user();
        $likeUserId = $likeUser->user_id;
        $userProfileLikeKey = 'user.' . $id . '.profile.like';
        if (Redis::zrank($userProfileLikeKey, $likeUserId) === null) {
            $user = $this->findOrFail($id);
            $like = $likeUser->profileLike()->updateOrCreate(array('profile_user_id' => $user->getKey()));
            event(new UserProfileLikeEvent($likeUser, $user, $like));
        }
    }

    public function profileRevokeLike($id)
    {
        $likeUser = auth()->user();
        $likeUserId = $likeUser->user_id;
        $userProfileLikeKey = 'user.' . $id . '.profile.like';
        if (Redis::zrank($userProfileLikeKey, $likeUserId) !== null) {
            $user = $this->findOrFail($id);
            $likeUser->profileLike()->delete();
            event(new UserProfileRevokeLikeEvent($likeUser, $user));
        }
    }



    public function onlineUsersCount()
    {
        $key = 'helloo:account:service:account-random-im-set';
        return Redis::scard($key);
    }

    public function filter()
    {
        $country_code = config('countries');
        $users = DB::table('ry_online_users');
        $user_gender = intval(request()->input('user_gender', 2));
        $country = strval(request()->input('country', 0));
        $country_op = intval(request()->input('country_op', 2));
        $user_country = array_search(strtoupper($country), $country_code);
        $userAge = strval(request()->input('user_age', "0,0"));
        $user_country_id = $user_country === false ? 0 : $user_country + 1;
        if ($country_op == 1) {
            !empty($user_country_id) && $users = $users->where('user_country_id', $user_country_id);
        } elseif ($country_op == 0) {
            !empty($user_country_id) && $users = $users->where('user_country_id', '!=', $user_country_id);
        }
        if (in_array($user_gender, array(0, 1))) {
            $users = $users->where('user_gender', $user_gender);
        }

        if ($userAge != '0,0') {
            $userAge = $userAge . ",";
            list ($userStartAge, $userEndAge) = explode(',', $userAge);
            $userStartAge = intval($userStartAge);
            $userEndAge = intval($userEndAge);
            if ($userStartAge > 0 && $userStartAge < $userEndAge && $userEndAge <= 100) {
                $users = $users->whereBetween('user_age', array($userStartAge, $userEndAge));
            }
        }
        $userIds = $users->inRandomOrder()->take(10)->get()->pluck('user_id')->toArray();
        return $this->findByMany($userIds);
    }

    public function planet()
    {
        $num = intval(request()->input('num', 55));
        $num = $num < 10 || $num > 100 ? 50 : $num;
        $key = 'helloo:account:service:account-random-im-set';
        $userIds = Redis::srandmember($key, $num);
        $backup = array(
            1,
            2,
            63,
            65,
            66,
            67,
            68,
            69,
            70,
            71,
            73,
            158,
            159,
            160,
            161,
            162,
            163,
            164,
            165,
            166,
            232,
            233,
            260,
            261,
            268,
            272,
            273,
            274,
            275,
            277,
            279,
            288,
            292,
            300,
            302,
            303,
            304,
            336,
            337,
            338,
            339,
            340,
            341,
            342,
            343,
            344,
            345,
            346,
            347,
            349,
            350,
            351,
            352,
            353,
            357,
        );
        if(count($userIds)<=50)
        {
            $userIds = array_merge($userIds , $backup);
            $userIds = array_unique($userIds);
            shuffle($userIds);
        }
        return array_slice($userIds , 0 , 52);
    }

    public function userFollow($userIds)
    {
        if (auth()->check() && !empty($userIds)) {
            return DB::table('common_follows')->where('user_id', auth()->id())->where('followable_type', "App\Models\User")->where('relation', "follow")->whereIn('followable_id', $userIds)->pluck('followable_id')->all();
        }
        return array();
    }

    public function isProhibited(User $user)
    {
        $userName = $user->user_name;
        $userNickName = $user->user_nick_name;
        $prohibited_operation_user = config('redis-key.user.prohibited_operation_user');
        $prohibited_operation_user_name = config('redis-key.user.prohibited_operation_user_name');
        if (Redis::exists($prohibited_operation_user)) {
            $rank = Redis::zrank($prohibited_operation_user, $user->user_id);
            if ($rank !== null) {
                return true;
            }
        }
        if (Redis::exists($prohibited_operation_user_name)) {
            $limit = 50;
            $count = Redis::zcard($prohibited_operation_user_name);
            $total = ceil($count / $limit);
            for ($i = 1; $i <= $total; $i++) {
                $offset = ($i - 1) * $limit;
                $prohibitedUserNames = Redis::zrangebyscore($prohibited_operation_user_name, "+inf", "-inf", array('withScores' => true, 'limit' => array($offset, $limit)));
                $prohibitedUserNames = array_keys($prohibitedUserNames);
                if (str_contains(strtolower($userName), $prohibitedUserNames) || str_contains(strtolower($userNickName), $prohibitedUserNames)) {
                    return true;
                }
            }

        }
        return false;
    }

    public function official($userId)
    {
        $key = 'helloo:account:service:account-official';
        return boolval(Redis::zrank($key , $userId)!==null);
    }





}
