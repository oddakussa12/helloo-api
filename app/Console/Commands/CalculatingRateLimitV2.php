<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Custom\RedisList;
use App\Traits\CachablePost;
use App\Traits\CachableUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class CalculatingRateLimitV2 extends Command
{
    use CachablePost,CachableUser;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculating:rate_limit_v2';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'calculating post rate limit v2';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $now = Carbon::now();
        $oneMonthAgo = $now->subDays(3)->format('Y-m-d 00:00:00');
        $i = intval($now->addMinutes(30)->format('i'));
        $i = $i<=0?1:$i;
        $index = ceil($i/30);
        $rateKey = config('redis-key.post.post_index_rate').'_'.$index;
        $rateV2Key = config('redis-key.post.post_index_rate_v2').'_'.$index;
        $newKey = config('redis-key.post.post_index_new');
        $nonRateKey = config('redis-key.post.post_index_non_rate');
        $preheatPropagandaKey = config('redis-key.post.post_preheat_propaganda');
//        Redis::del($rateKey);
        Redis::del($rateV2Key);
        $perPage = 10;
        $userKolX = intval(Redis::get('user_kol_x'));
        $postInitCommentNum = intval(Redis::get('post_init_comment_num'));
        $count = Redis::zcard($newKey);
        $redis = new RedisList();
        $postGravity = floatval(Redis::get('post_gravity'));
        $postGravity = $postGravity<=0||$postGravity>=2?1:$postGravity;
        $lastPage = ceil($count/$perPage);
        for ($page=1;$page<=$lastPage;$page++) {
            $offset = ($page-1)*$perPage;
            $posts = $redis->zRevRangeByScore($newKey , '+inf', strtotime($oneMonthAgo) , true, array($offset, $perPage));
            if(empty($posts))
            {
                break;
            }
            foreach ($posts as $postId=>$time)
            {
                if(Redis::sismember($nonRateKey , $postId))
                {
                    continue;
                }
                $posts = $this->getPost($postId , array('user_id' , 'comment_num' , 'real_like'));
                $commentCount = isset($posts['comment_num'])?$posts['comment_num']:0;
                $likeCount = isset($posts['real_like'])?$posts['real_like']:0;
                $commenterCount= $this->commenterCount($postId);
                $countryCount = $this->countryNum($postId);
                $index = Redis::zrank($preheatPropagandaKey , $postId);
                if($index===null)
                {
                    $commentCount = $commentCount + $postInitCommentNum;
                }
                $user = $this->getUser($posts['user_id'] , array('user_level'));
                $x = $user['user_level']==1?$userKolX:1;
                $x = $x<0?1:$x;
                $rate = rate_comment_v4($commentCount , Carbon::createFromTimestamp($time)->toDateTimeString() , $likeCount , $commenterCount , $countryCount , $postGravity , $x);
//                Storage::prepend('rate_'.strval($index).'.log', strval($postId).','.strval($rate).','.strval($likeCount).','.strval($postGravity).PHP_EOL);
//                Redis::zadd($rateKey , rate_comment_v3($commentCount , Carbon::createFromTimestamp($time)->toDateTimeString() , $likeCount , $commenterCount , $countryCount) , $postId);
                Redis::zadd($rateV2Key , $rate , $postId);
            }
        }
    }
}
