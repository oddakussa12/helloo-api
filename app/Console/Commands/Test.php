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
        $talks = DB::table('escort_talker')->select('user_id')->distinct()->get()->toArray();
        foreach ($talks as $talk)
        {
            $talk = collect($talk)->toArray();
            $sender = collect(DB::table('users')->where('user_id' , $talk['user_id'])->first())->toArray();
            if(blank($sender))
            {
                Log::info('empty_$sender' , array($talk['user_id']));
                continue;
            }
            $content = array(
                'content'=>'video message',
                'user'=> array(
                    'id'=>$sender['user_id'],
                    'name'=>$sender['user_nick_name'],
                    'portrait'=>userCover($sender['user_avatar']),
                    'extra'=>array(
                        'userLevel'=>$sender['user_level']
                    ),
                ),
                'videoUrl'=>"https://video.helloo.mantouhealth.com/other/20210129/20210129112004.mp4",
                'firstFrameUrl'=>"https://image.helloo.mantouhealth.com/other/20210129/20210129112004.png",
                'videoPath'=>'',
                'firstFramePath'=>'',
            );
            $content = array(
                'senderId'   => $talk['user_id'],
//                'targetId'   => $targetId,
                "objectName" => "Helloo:VideoMsg",
                'content'    => \json_encode($content),
                'pushContent'=>'video message',
                'pushExt'=>\json_encode(array(
                    'title'=>'video message',
                    'forceShowPushContent'=>1
                ))
            );

            DB::table('users_friends')->where('user_id' , $talk['user_id'])->orderByDesc('friend_id')->chunk(10 , function($friends) use ($content){
                foreach ($friends as $friend)
                {
                    $content['targetId'] = $friend->friend_id;
                    Log::info('$friend->friend_id' , array($friend->friend_id));
                    $this->send($content);
                }
            });
        }
//        DB::table('signup_infos')->whereNotNull('signup_ip')->where('signup_isocode' , 'US')->orderByDesc('signup_id')->chunk(10 , function($users){
//            foreach ($users as $user)
//            {
//
//                $geo = geoip($user->signup_ip);
//                Log::info('sign_up' , array(
//                    $user->signup_id,
//                    $user->signup_ip,
//                    $geo->iso_code
//                ));
//                $signup_info = array();
//                $signup_info['signup_isocode'] = $geo->iso_code;
//                $signup_info['signup_country'] = $geo->country;
//                $signup_info['signup_state'] = $geo->state_name;
//                $signup_info['signup_city'] = $geo->city;
//                $signup_info['signup_lat'] = $geo->lat;
//                $signup_info['signup_lon'] = $geo->lon;
//                $signup_info['signup_timezone'] = $geo->timezone;
//                $signup_info['signup_continent'] = $geo->continent;
//                DB::table('signup_infos')->where('signup_id' , $user->signup_id)->update($signup_info);
//            }
//        });
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

    public function send($content)
    {
        Log::info('escort_talk_content' , $content);
        $result = app('rcloud')->getMessage()->Person()->send($content);
        Log::info('escort_talk_result' , $result);
    }

}
