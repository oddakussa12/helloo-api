<?php

namespace App\Console\Commands;

use App\Custom\Uuid\RandomStringGenerator;
use App\Models\User;
use App\Traits\CachableUser;
use App\Jobs\Test as TestJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Ramsey\Uuid\Uuid;
use App\Foundation\Auth\User\Update;
use App\Repositories\Contracts\UserRepository;


class Test extends Command
{
    use CachableUser,Update;
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
        DB::table('signup_infos')->where('signup_id' , ">=" , 60007)->where('signup_isocode' , 'US')->orderByDesc('signup_id')->chunk(10 , function($users){
            foreach ($users as $user)
            {

                $geo = geoip($user->signup_ip);
                Log::info('sign_up' , array(
                    $user->signup_id,
                    $user->signup_ip,
                    $geo->iso_code
                ));
                $signup_info = array();
                $signup_info['signup_isocode'] = $geo->iso_code;
                $signup_info['signup_country'] = $geo->country;
                $signup_info['signup_state'] = $geo->state_name;
                $signup_info['signup_city'] = $geo->city;
                $signup_info['signup_lat'] = $geo->lat;
                $signup_info['signup_lon'] = $geo->lon;
                $signup_info['signup_timezone'] = $geo->timezone;
                $signup_info['signup_continent'] = $geo->continent;
                DB::table('signup_infos')->where('signup_id' , $user->signup_id)->update($signup_info);
            }
        });
//        $evenPhoneKey = "helloo:account:service:account-phone-{even}-number";
//        $oddPhoneKey = "helloo:account:service:account-phone-{odd}-number";
//        $evenData = array();
//        $oddData = array();
//        $ageSortSetKey = 'helloo:account:service:account-age-sort-set';
//        User::chunk(500, function($users){
//            foreach($users as $user){
//                Redis::del('helloo:account:service:account-ry-token:'.$user->user_id);
//                Redis::del('helloo:account:service:account:'.$user->user_id);
//            }
//        });
        die;
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
