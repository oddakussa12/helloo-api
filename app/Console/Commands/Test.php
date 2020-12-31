<?php

namespace App\Console\Commands;

use App\Custom\Uuid\RandomStringGenerator;
use App\Models\User;
use App\Traits\CachableUser;
use App\Jobs\Test as TestJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;


class Test extends Command
{
    use CachableUser;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto test';

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
        $evenPhoneKey = "helloo:account:service:account-phone-{even}-number";
        $oddPhoneKey = "helloo:account:service:account-phone-{odd}-number";
        $evenData = array();
        $oddData = array();
        DB::table('users_phones')->orderByDesc('phone_id')->chunk(100 , function ($phones) use ($evenPhoneKey , $oddPhoneKey , $evenData , $oddData){
            foreach ($phones as $phone)
            {
                $key = 'helloo:account:service:account-ry-token:'.$phone->user_id;
                Redis::del($key);
//                DB::table('users')->insert(array(
//                    'user_id'=>$phone->user_id,
//                    'user_pwd'=>bcrypt(123456),
//                    'user_nick_name'=>(new RandomStringGenerator())->generate(6),
//                    'user_gender'=>mt_rand(0 ,1),
//                    'user_avatar'=>"default_avatar_".mt_rand(1 , 18).'.png',
//                    'user_birthday'=>Carbon::now()->subYears(mt_rand(5 , 20))->toDateString(),
//                    'user_created_at'=>Carbon::now()->subDays(mt_rand(5 , 20))->toDateTimeString(),
//                    'user_updated_at'=>Carbon::now()->subDays(mt_rand(5 , 20))->toDateTimeString(),
//                    'user_activated_at'=>Carbon::now()->subDays(mt_rand(5 , 20))->toDateTimeString(),
//                    'user_answered_at'=>Carbon::now()->subDays(mt_rand(5 , 20))->toDateTimeString(),
//                    'user_answer'=>1,
//                    'user_activation'=>1,
//                ));
            }
//            Redis::zadd($evenPhoneKey , $evenData);
//            Redis::zadd($oddPhoneKey , $oddData);
//            $evenData = $oddData = array();
        });
        die;
        $ageSortSetKey = 'helloo:account:service:account-age-sort-set';
        User::chunk(100, function($users) use ($ageSortSetKey){
            foreach($users as $user){
                if($user->user_activation==1)
                {
                    $age = age($user->user_birthday);
                    Redis::zadd($ageSortSetKey , $age , $user->getKey());
                }
//                $cache = collect($user)->toArray();
//                $key = "helloo:account:service:account:".$user->getKey();
//                Redis::hmset($key , $cache);
//                Redis::expire($key , 60*60*24*30);
            }
        });
//        $now = Carbon::now();
//        $oneMonthAgo = $now->subDays(3)->format('Y-m-d 00:00:00');
//        $newKey = config('redis-key.post.post_index_new');
//        $perPage = 10;
//        $count = Redis::zcard($newKey);
//        $redis = new RedisList();
//        $lastPage = ceil($count/$perPage);
//        for ($page=1;$page<=$lastPage;$page++) {
//            $offset = ($page - 1) * $perPage;
//            $posts = $redis->zRevRangeByScore($newKey, '+inf', strtotime($oneMonthAgo), true, array($offset, $perPage));
//            if (empty($posts)) {
//                break;
//            }
//            foreach ($posts as $postId=>$time)
//            {
//                $postKey = 'post.'.$postId.'.data';
//                $after = $this->likeCount($postId);
//                $coefficient = floatval(Redis::get('fake_like_coefficient'));
//                Redis::hmset($postKey , array('temp_like'=>fakeLike($after['like'] , $coefficient)));
//            }
//        }
    }

}
