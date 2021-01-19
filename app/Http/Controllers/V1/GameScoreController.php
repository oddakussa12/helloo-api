<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use App\Jobs\FriendScore;
use Illuminate\Http\Request;
use Godruoyi\Snowflake\Snowflake;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Resources\AnonymousCollection;
use App\Repositories\Contracts\UserRepository;


class GameScoreController extends BaseController
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Log::info('all' , $request->all());
        $jti = JWTAuth::getClaim('jti');
        $userId = auth()->id();
        $game = strval($request->input('game' , ''));
        $score = strval($request->input('score' , 0));
        $plaintext = opensslDecrypt($score , $jti);
        $score = intval($plaintext);
        Log::info('test' , array(
            'game'=>$game,
            'score'=>$score,
            'jti'=>$jti,
            'plaintext'=>$plaintext,
        ));
        if($score<=0||!in_array($game , array('coronation' , 'superZero' , 'trumpAdventures')))
        {
            return $this->response->created();
        }
        $phone = DB::table('users_phones')->where('user_id' , $userId)->first();
        $oldRank = $rank = $scoreBefore = 0;
        $users = array();
        if(!blank($phone))
        {
            $country = $phone->user_phone_country;
            if(!in_array($country , array(1 , 670 , '1' , '670')))
            {
                $country = 'other';
            }
            $key = "helloo:account:game:country:score:".$game.'-'.$country;
            $now = Carbon::now()->timestamp;
            $nowFlake = new Snowflake();
            $scoreBefore = $max = Redis::zscore($key , $userId);
            $oldRank = Redis::zrevrank($key , $userId);
            $oldRank = $oldRank===null?0:$oldRank+1;//全国排行
            DB::beginTransaction();
            try{
                $result = DB::table('users_games')->insert(
                    array(
                        'id'=>$nowFlake->id(),
                        'user_id'=>$userId ,
                        'game'=>$game ,
                        'country'=>$country ,
                        'score'=>$score ,
                        'created_at'=>$now
                    )
                );
                if(!$result)
                {
                    throw new \Exception('score store failed!' , 1001);
                }
                if($max===null)
                {
                    $result = DB::table('users_games_scores')->insert(
                        array(
                            'id'=>$nowFlake->id() ,
                            'user_id'=>$userId ,
                            'game'=>$game ,
                            'score'=>$score ,
                            'country'=>$country,
                            'created_at'=>$now,
                            'updated_at'=>$now,
                        )
                    );
                    if(!$result)
                    {
                        throw new \Exception('score update insert failed!' , 1002);
                    }
                    Redis::zadd($key , $score , $userId);
                }else{
                    if($score>$max)
                    {
                        $count = DB::table('users_games_scores')->where('user_id' , $userId)->where('game' , $game)->where('country' , $country)->update(
                            array('score'=>$score , 'updated_at'=>$now)
                        );
                        if($count<=0)
                        {
                            throw new \Exception('score update failed!' , 1003);
                        }
                        Redis::zadd($key , $score , $userId);
                    }
                }

                DB::commit();
            }catch (\Exception $e)
            {
                $code = $e->getCode();
                DB::rollBack();
                Log::info('score_fail' , array(
                    'user_id'=>$userId,
                    'param'=>$request->all(),
                    'code'=>$code,
                    'message'=>$e->getMessage(),
                ));
                abort('523' , 'Server Error!');
            }


            $this->setDayRank($game , $userId , $country , $score);
            $this->setWeekRank($game , $userId , $country , $score);


            $rank = intval(Redis::zrevrank($key , $userId))+1;//全国排行
            $sortKey = "helloo:account:friend:game:rank:sort:".$userId.'-'.$game;
            if(!Redis::exists($sortKey))
            {
                Log::info('$sortKey' , array($sortKey , 'no exists'));
                $this->friendRank($userId , $game , $score , $max , $country);
            }else{
                Log::info('$sortKey' , array($sortKey , 'exists'));
                Redis::zadd($sortKey , intval(Redis::zscore($key , $userId)) , $userId);
            }
            Log::info('$sortKey' , array($sortKey));
            $selfRank = Redis::zrevrank($sortKey , $userId);
            Log::info('$selfRank' , array($selfRank));
            if($selfRank==0)
            {
                $start = $selfRank;
                $end = $selfRank+2;
            }else{
                $start = $selfRank-1;
                $end = $selfRank+1;
            }
            Log::info('$sortKey' , array($sortKey));
            Log::info('$start' , array($start));
            Log::info('$end' , array($end));
            $userIds = Redis::zrevrange($sortKey , $start , $end , array('withScores'=>true));
            Log::info('$userIds' , array($userIds));
            $users = app(UserRepository::class)->findByUserIds(array_keys($userIds));
            foreach ($users as $i=>$user)
            {
                $users[$i]['score'] = $userIds[$user['user_id']];
                $ranks = Redis::zrevrank($sortKey , $user['user_id']);
                $ranks = $ranks===null?$ranks=0:$ranks+1;
                $users[$i]['rank'] = $ranks;
            }
            intval($scoreBefore)<$score&&$this->dispatchNow(new FriendScore($userId , $score , $game));
        }
        $data = array(
            'oldRank'=>$oldRank,
            'rank'=>$rank,
            'user'=>collect($users)->toArray(),
            'scoreBefore'=>intval($scoreBefore)
        );
        Log::info('score_return' , $data+array('user_id'=>$userId));
        return $this->response->created(null , $data);
    }

    public function friendRank($userId , $game , $score , $max , $country)
    {
        $userScores = array();
        $sortKey = "helloo:account:friend:game:rank:sort:".$userId.'-'.$game;
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
        if($max===null)
        {
            $userScores[$userId] = $score;
        }else{
            if($max>$score)
            {
                $userScores[$userId] = $max;
            }else{
                $userScores[$userId] = $score;
            }
        }
        if(!blank($userScores))
        {
            Redis::zadd($sortKey , $userScores);
            Redis::expire($sortKey , 60*60*24*30);
        }
    }

    public function setDayRank($game , $userId , $country , $score)
    {
        if($country==1)
        {
            $date = Carbon::now('America/Grenada')->toDateString();
        }elseif($country==670){
            $date = Carbon::now('Asia/Dili')->toDateString();
        }else{
            $country = 'other';
            $date = Carbon::now()->toDateString();
        }
        $key = "helloo:account:game:score:day:rank:".$game.'-'.$country.'-'.$date;
        $maxScore = Redis::zscore($key , $userId);
        if($maxScore===null)
        {
            Redis::zadd($key ,$score , $userId);
        }else{
            if($maxScore<$score)
            {
                Redis::zadd($key ,$score , $userId);
            }
        }
    }

    public function setWeekRank($game , $userId , $country , $score)
    {
        if($country==1)
        {
            $date = Carbon::now('America/Grenada')->endOfWeek()->toDateString();
        }elseif($country==670){
            $date = Carbon::now('Asia/Dili')->endOfWeek()->toDateString();
        }else{
            $country = 'other';
            $date = Carbon::now()->endOfWeek()->toDateString();
        }
        $key = "helloo:account:game:score:week:rank:".$game.'-'.$country.'-'.$date;
        $maxScore = Redis::zscore($key , $userId);
        if($maxScore===null)
        {
            Redis::zadd($key ,$score , $userId);
        }else{
            if($maxScore<$score)
            {
                Redis::zadd($key ,$score , $userId);
            }
        }
    }

    public function day($game)
    {
        $phone = DB::table('users_phones')->where('user_id' , auth()->id())->first();
        $country = $phone->user_phone_country;
        if($country==1)
        {
            $date = Carbon::yesterday('America/Grenada')->toDateString();
            $start = Carbon::yesterday('America/Grenada')->startOfDay()->timestamp;
            $end = Carbon::yesterday('America/Grenada')->endOfDay()->timestamp;
        }elseif($country==670){
            $date = Carbon::yesterday('Asia/Dili')->toDateString();
            $start = Carbon::yesterday('Asia/Dili')->startOfDay()->timestamp;
            $end = Carbon::yesterday('Asia/Dili')->endOfDay()->timestamp;
        }else{
            $country = 'other';
            $date = Carbon::yesterday()->toDateString();
            $start = Carbon::yesterday()->startOfDay()->timestamp;
            $end = Carbon::yesterday()->endOfDay()->timestamp;
        }
        Log::info('$start' , array($start));
        Log::info('$end' , array($end));
        $key = "helloo:account:game:score:day:rank:".$game.'-'.$country.'-'.$date;
        Log::info('key' , array($key));
        $options = array('withScores'=>true , 'limit'=>array(0,10));
        $scores = Redis::zrevrangebyscore($key , '+inf' , '-inf' ,  $options);
        $userIds = array_keys($scores);
        Log::info('userIds' , $userIds);
        $scores = DB::table('users_games')->whereIn('user_id' , $userIds)->where('created_at' , '>=' , $start)->where('created_at' , '<=' , $end)->orderByDesc('score')->orderBy('created_at')->limit(1000)->select(array(
            'user_id' , 'game' , 'country' , 'score'
        ))->get()->unique('user_id');
        $userIds = $scores->pluck('user_id')->toArray();
        $users = app(UserRepository::class)->findByUserIds($userIds);
        $tags = app(UserRepository::class)->findTagByUserIds($userIds);
        $users->each(function($user , $index) use ($tags){
            $tag = $tags->where('user_id' , $user['user_id'])->first();
            $user['gameTag'] = $tag['tag'];
            $user['gameTagColor'] = $tag['color'];
        });

        $scores = collect($scores)->each(function($score , $index) use ($users){
            $user = $users->where('user_id' , $score->user_id)->first();
            $score->user = $user;
        })->sortByDesc('score')->values();
        return AnonymousCollection::collection($scores);
    }

    public function week($game)
    {
        $phone = DB::table('users_phones')->where('user_id' , auth()->id())->first();
        $country = $phone->user_phone_country;
        if($country==1)
        {
            $date = Carbon::now('America/Grenada')->previousWeekendDay()->toDateString();
            $start = Carbon::now('America/Grenada')->previousWeekendDay()->startOfWeek()->timestamp;
            $end = Carbon::now('America/Grenada')->previousWeekendDay()->endOfDay()->timestamp;
        }elseif($country==670){
            $date = Carbon::now('Asia/Dili')->previousWeekendDay()->toDateString();
            $start = Carbon::now('Asia/Dili')->previousWeekendDay()->startOfWeek()->timestamp;
            $end = Carbon::now('Asia/Dili')->previousWeekendDay()->endOfDay()->timestamp;
        }else{
            $country = 'other';
            $date = Carbon::now()->previousWeekendDay()->toDateString();
            $start = Carbon::now()->previousWeekendDay()->startOfWeek()->timestamp;
            $end = Carbon::now()->previousWeekendDay()->endOfDay()->timestamp;
//            $date = Carbon::now()->endOfWeek()->toDateString();
//            $start = Carbon::now()->startOfWeek()->timestamp;
//            $end = Carbon::now()->endOfDay()->timestamp;
        }
        Log::info('$date' , array($date));
        Log::info('$end' , array($end));
        Log::info('$end' , array($end));
        $key = "helloo:account:game:score:week:rank:".$game.'-'.$country.'-'.$date;
        Log::info('key' , array($key));
        $options = array('withScores'=>true , 'limit'=>array(0,10));
        $scores = Redis::zrevrangebyscore($key , '+inf' , '-inf' ,  $options);
        $userIds = array_keys($scores);
        Log::info('userIds' , $userIds);
        $scores = DB::table('users_games')->whereIn('user_id' , $userIds)->where('created_at' , '>=' , $start)->where('created_at' , '<=' , $end)->orderByDesc('score')->orderBy('created_at')->limit(1000)->select(array(
            'user_id' , 'game' , 'country' , 'score'
        ))->get()->unique('user_id');
        $userIds = $scores->pluck('user_id')->toArray();
        $users = app(UserRepository::class)->findByUserIds($userIds);
        $tags = app(UserRepository::class)->findTagByUserIds($userIds);
        $users->each(function($user , $index) use ($tags){
            $tag = $tags->where('user_id' , $user['user_id'])->first();
            $user['gameTag'] = $tag['tag'];
            $user['gameTagColor'] = $tag['color'];
        });

        $scores = collect($scores)->each(function($score , $index) use ($users){
            $user = $users->where('user_id' , $score->user_id)->first();
            $score->user = $user;
        })->sortByDesc('score')->values();
        return AnonymousCollection::collection($scores);
    }

}
