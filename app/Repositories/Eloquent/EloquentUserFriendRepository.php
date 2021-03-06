<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 2019/5/19
 * Time: 18:35
 */
namespace App\Repositories\Eloquent;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Repositories\EloquentBaseRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\UserFriendRepository;

class EloquentUserFriendRepository  extends EloquentBaseRepository implements UserFriendRepository
{
    /**
     * @note 通过用户ID查询好友
     * @datetime 2021-07-12 19:19
     * @param $userId
     * @return mixed
     */
    public function paginateByUser($userId)
    {
        return $this->model->where('user_id' , $userId)->orderBy($this->model->getCreatedAtColumn(), 'DESC')->groupBy('friend_id')->paginate(50 , ["*"] , $this->pageName);
    }

    /**
     * @note
     * @datetime 2021-07-12 19:19
     * @param $userId
     * @param int $perPage
     * @return mixed
     */
    public function getAllByUser($userId, $perPage = 15)
    {
        return $this->model->where('user_id' , $userId)->orderBy($this->model->getCreatedAtColumn(), 'DESC')->groupBy('friend_id')->paginate($this->perPage , ['*'] , $this->pageName);
    }

    /**
     * @note 获取好友排行
     * @datetime 2021-07-12 19:20
     * @param $userId
     * @param $game
     * @param string $country
     * @return \Illuminate\Support\Collection
     */
    public function getFriendRankByUserId($userId , $game , $country='other')
    {
        $userScores = array();
        $cacheKey = "helloo:account:friend:game:rank:".$userId.$game;
        $data = Redis::get($cacheKey);
        if($data!==null)
        {
            $users = collect(\json_decode($data , true));
        }else{
            $sortKey = "helloo:account:friend:game:rank:sort:".$userId.'-'.$game;
            if(Redis::exists($sortKey))
            {
                $options = array('withScores'=>true , 'limit'=>array(0,1000));
                $userScores = Redis::zrevrangebyscore($sortKey , '+inf' , '-inf' ,  $options);
                $userIds = array_keys($userScores);
            }else{
                $phone = DB::table('users_phones')->where('user_id' , $userId)->first();
                $country = $phone->user_phone_country;
                if(!in_array($country , array(62 , 670 , '62' , '670' , 251 , '251')))
                {
                    $country = 'other';
                }
                $key = "helloo:account:game:country:score:".$game.'-'.$country;
                DB::table('users_friends')
                    ->where('user_id' , $userId)
                    ->orderByDesc('created_at')
                    ->chunk(100, function($friends) use (&$userScores , $key){
                        foreach ($friends as $friend)
                        {
                            $score = intval(Redis::zscore($key , $friend->friend_id));
                            if($score>0)
                            {
                                $userScores[$friend->friend_id] = $score;
                            }
                        }
                    });
                $selfScore = intval(Redis::zscore($key , $userId));
                if($selfScore>0)
                {
                    $userScores[$userId] = $selfScore;
                }
                if(!blank($userScores))
                {
                    Redis::zadd($sortKey , $userScores);
                    Redis::expire($sortKey , 60*60*24*30);
                }
                $userIds = array_keys($userScores);
            }
            $users = app(UserRepository::class)->findByUserIds($userIds);
            $tags = app(UserRepository::class)->findTagByUserIds($userIds);
            $users = $users->each(function($user , $index) use ($userScores , $tags){
                $user['score'] = $userScores[$user['user_id']];
                $tag = $tags->where('user_id' , $user['user_id'])->first();
                $user['gameTag'] = $tag['tag'];
                $user['gameTagColor'] = $tag['color'];
            })->sortByDesc('score')->values();
            Redis::set($cacheKey , \json_encode($users->toArray()));
            Redis::expire($cacheKey , 600);
        }
        return $users;
//
//
//        if($data===null)
//        {
//            $phone = DB::table('users_phones')->where('user_id' , $userId)->first();
//            $country = $phone->user_phone_country;
//            $key = "helloo:account:game:country:score:".$game.'-'.$country;
//            DB::table('users_friends')
//                ->where('user_id' , $userId)
//                ->orderByDesc('created_at')
//                ->chunk(100, function($friends) use (&$userScores , $key){
//                    foreach ($friends as $friend)
//                    {
//                        $score = intval(Redis::zscore($key , $friend->friend_id));
//                        if($score>0)
//                        {
//                            $userScores[$friend->friend_id] = $score;
//                        }
//                    }
//                });
//            $sortKey = "helloo:account:friend:game:rank:sort:".$userId.'-'.$game;
//            if(!blank($userScores))
//            {
//                Redis::del($sortKey);
//                Redis::zadd($sortKey , $userScores);
//                Redis::expire($sortKey , 60*60*24*7);
//            }
//            $userIds = array_keys($userScores);
//            $users = app(UserRepository::class)->findByUserIds($userIds);
//            $tags = app(UserRepository::class)->findTagByUserIds($userIds);
//            $users = $users->each(function($user , $index) use ($userScores , $tags){
//                $user['score'] = $userScores[$user['user_id']];
//                $tag = $tags->where('user_id' , $user['user_id'])->first();
//                $user['gameTag'] = $tag['tag'];
//                $user['gameTagColor'] = $tag['color'];
//            })->sortByDesc('score')->values();
//            Redis::set($cacheKey , \json_encode($users->toArray()));
//            Redis::expire($cacheKey , 60*5);
//        }else{
//            $users = collect(\json_decode($data , true));
//        }
//        return $users;
    }
}
