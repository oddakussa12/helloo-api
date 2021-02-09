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
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Ramsey\Uuid\Uuid;
use App\Foundation\Auth\User\Update;
use App\Repositories\Contracts\UserRepository;
use App\Custom\EasySms\PhoneNumber;


class Test extends Command
{
    use CachableUser,Update;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:test {type} {user}';

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
        $this->fixSchool();
    }

    public function fixSchool()
    {
        $schools = DB::table('schools')->pluck('name' , 'key')->toArray();
        DB::table('users')->where('user_activation' , 1)->orderByDesc('user_created_at')->chunk(100 , function($users) use ($schools){
            foreach ($users as $user)
            {
                if(blank($user->user_sl)&&!blank($user->user_school))
                {
                    $school = $user->user_school;
                    if(isset($schools[$school]))
                    {
                        DB::table('users')->where('user_id' , $user->user_id)->update(array(
                            'user_sl'=>$schools[$school],
                        ));
                    }

                }
            }
        });
    }



    public function sms()
    {
        $file = storage_path('app/tmp/2.csv');
        $sms = app('easy-sms');

        $str = <<<DOC
NEW Lovbee Promotion!

Thank you joining our Lovbee Family. You have been selected to get $12 FREE data. All you need to do is add 3 friends to your Lovbee contacts ans you instantly win phone topup! It's that simple.

Free friends for you!
Add these contacts and start chatting now!
Sweets123
Septemberborn2005
kiannabain473
ramenandanime4L
Sheneal123
Queencess100

www.lovbee.fun
DOC;
        $i = 0;
        $f = 0;
        foreach(file($file) as $line) {
            list($country , $phone) = explode(',' , $line);
            $number = new PhoneNumber(trim($phone) , trim($country));
            try{
                $result = $sms->send($number, $str , array('aws'));
                Log::info('$result' , array($result));
                $i++;
            }catch (NoGatewayAvailableException $e)
            {
                $exception = $e->getLastException();
                Log::info('error' , array('$phone'=>$phone , 'message'=>$exception->getMessage()));
                $f++;
            }
            usleep(200);
        }
        echo PHP_EOL;
        echo $i;
        echo PHP_EOL;
        echo $f;
    }

    public function system()
    {
        $key = "helloo:account:system:senderId";
        $systemId = Redis::get($key);
        if($systemId===null)
        {
            return;
        }
        $sender = app(UserRepository::class)->findByUserId(intval($systemId))->toArray();
        $content = array(
            'senderId'   => $sender['user_id'],
            "objectName" => "Helloo:VideoMsg",
            'content'    => array(
                'content'=>'video message',
                'user'=> array(
                    'id'=>$sender['user_id'],
                    'name'=>$sender['user_nick_name'],
                    'portrait'=>$sender['user_avatar_link'],
                    'extra'=>array(
                        'userLevel'=>$sender['user_level']
                    ),
                ),
                'videoPath'=>'',
                'firstFramePath'=>'',
                'firstFrameUrl'=>'https://image.helloo.mantouhealth.com/other/20210204/6534e259a03675491654d58ce1c94969.png',
                'videoUrl'=>'https://video.helloo.mantouhealth.com/other/20210204/6534e259a03675491654d58ce1c94969.mp4',
            ),
            'pushContent'=>'video message',
            'pushExt'=>\json_encode(array(
                'title'=>'video message',
                'forceShowPushContent'=>1
            ))
        );
        dump($content);
        $this->sendSystem($content);
//        DB::table('users_phones')->orderByDesc('phone_id')->chunk(1000 , function($users) use ($content){
//            $userIds = collect($users)->pluck('user_id')->toArray();
//            $content['targetId'] = $userIds;
//            $this->sendSystem($content);
//        });
    }

    public function custom($talker)
    {
        $sender = collect(DB::table('users')->where('user_id' , $talker)->first())->toArray();
        if(blank($sender))
        {
            return;
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
            'videoUrl'=>"http://video.helloo.mantouhealth.com/other/20210204/d0c6acc77db5a4cc5aceb31252f894c1.mp4",
            'firstFrameUrl'=>"https://image.helloo.mantouhealth.com/other/20210204/d0c6acc77db5a4cc5aceb31252f894c1.png",
            'videoPath'=>'',
            'firstFramePath'=>'',
        );
        $content = array(
            'senderId'   => $talker,
            "objectName" => "Helloo:VideoMsg",
            'content'    => \json_encode($content),
            'pushContent'=>'video message',
            'pushExt'=>\json_encode(array(
                'title'=>'video message',
                'forceShowPushContent'=>1
            ))
        );
        DB::table('signup_infos')->whereIn('signup_isocode' , array('au' , 'tl'))->orderByDesc('signup_id')->chunk(100 , function($users) use ($talker , $content){
            $userIds = collect($users)->pluck('user_id')->toArray();
            $userIds = array_diff($userIds , array($talker));
            $content['targetId'] = $userIds;
            $this->sendSystemPerson($content);
        });
    }

    public function person()
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
                    $this->sendPerson($content);
                }
            });
        }
    }

    public function sendPerson($content)
    {
        Log::info('sendPerson_content' , $content);
        $result = app('rcloud')->getMessage()->Person()->send($content);
        Log::info('sendPerson_result' , $result);
    }

    public function sendSystem($content)
    {
        Log::info('sendSystem_content' , $content);
        $result = app('rcloud')->getMessage()->System()->broadcast($content);
        Log::info('sendSystem_result' , $result);
    }

    public function sendSystemPerson($content)
    {
        sleep(10);
        Log::info('sendSystemPerson_content' , $content);
        $result = app('rcloud')->getMessage()->System()->send($content);
        Log::info('sendSystemPerson_result' , $result);
    }

}
