<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Repositories\Contracts\UserRepository;


class Message extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'message:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Message send';

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
        $switch = Redis::get('helloo:message:service:switch');
        Redis::del('helloo:message:service:switch');
        if($switch==1)
        {
            $pushes = DB::table('push_logs')->where('status' , 0)->get();
            DB::table('push_logs')->where('status' , 0)->update(array(
                'status'=>1
            ));
            foreach ($pushes as $push)
            {
                $sender = DB::table('users')->where('user_id' , $push->sender)->first();
                if(blank($sender))
                {
                    continue;
                }
                if($sender->user_level==1)
                {
                    if($push->type==0)
                    {
                        $this->system($push->sender , $push->video , $push->image);
                    }elseif ($push->type==1)
                    {
                        $country = explode(',' , $push->target);
                        DB::table('signup_infos')->whereIn('signup_isocode' , $country)->orderByDesc('signup_id')->chunk(100 , function($users) use ($push){
                            $userIds = collect($users)->pluck('user_id')->toArray();
                            $userIds = array_diff($userIds , array($push->sender));
                            foreach ($userIds as $userId)
                            {
                                usleep(200);
                                $this->system($push->sender , $push->video , $push->image , $userId);
                            }
                        });
                    }elseif ($push->type==2)
                    {
                        $this->system($push->sender , $push->video , $push->image , $push->target);
                    }elseif ($push->type==3)
                    {
                        DB::table('users_friends')->where('user_id' , $push->sender)->orderByDesc('friend_id')->chunk(100 , function($users) use ($push){
                            $userIds = collect($users)->pluck('friend_id')->toArray();
                            $userIds = array_diff($userIds , array($push->sender));
                            foreach ($userIds as $userId)
                            {
                                usleep(200);
                                $this->system($push->sender , $push->video , $push->image , $userId);
                            }
                        });
                    }
                }else{
                    if($push->type==0)
                    {
                        Log::info('person all push');
//                        DB::table('signup_infos')->orderByDesc('signup_id')->chunk(100 , function($users) use ($push){
//                            $userIds = collect($users)->pluck('user_id')->toArray();
//                            $userIds = array_diff($userIds , array($push->sender));
//                            foreach ($userIds as $userId)
//                            {
//                                usleep(200);
//                                $this->person($push->sender , $push->video , $push->image , $userId);
//                            }
//                        });
                    }elseif ($push->type==1)
                    {
                        Log::info('person country push');
//                        $country = explode(',' , $push->target);
//                        DB::table('signup_infos')->whereIn('signup_isocode' , $country)->orderByDesc('signup_id')->chunk(100 , function($users) use ($push){
//                            $userIds = collect($users)->pluck('user_id')->toArray();
//                            $userIds = array_diff($userIds , array($push->sender));
//                            foreach ($userIds as $userId)
//                            {
//                                usleep(200);
//                                $this->person($push->sender , $push->video , $push->image , $userId);
//                            }
//                        });
                    }elseif ($push->type==2)
                    {
                        $this->person($push->sender , $push->video , $push->image , $push->target);
                    }elseif ($push->type==3)
                    {
                        DB::table('users_friends')->where('user_id' , $push->sender)->orderByDesc('friend_id')->chunk(100 , function($users) use ($push){
                            $userIds = collect($users)->pluck('friend_id')->toArray();
                            $userIds = array_diff($userIds , array($push->sender));
                            foreach ($userIds as $userId)
                            {
                                usleep(200);
                                $this->person($push->sender , $push->video , $push->image , $userId);
                            }
                        });
                    }
                }
            }
        }
    }

    public function system($senderId , $videoUrl , $firstFrameUrl , $targetId=0)
    {
        if($targetId>0)
        {
            $sender = app(UserRepository::class)->findByUserId(intval($senderId))->toArray();
            $content = array(
                'senderId'   => $sender['user_id'],
                'targetId'   => $targetId,
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
                    'videoUrl'=>$videoUrl,
                    'firstFrameUrl'=>$firstFrameUrl,
                ),
                'pushContent'=>'video message',
                'pushExt'=>\json_encode(array(
                    'title'=>'video message',
                    'forceShowPushContent'=>1
                ))
            );
            $this->sendSystemPersonMessage($content);
        }else{
            $sender = app(UserRepository::class)->findByUserId(intval($senderId))->toArray();
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
                    'videoUrl'=>$videoUrl,
                    'firstFrameUrl'=>$firstFrameUrl,
                ),
                'pushContent'=>'video message',
                'pushExt'=>\json_encode(array(
                    'title'=>'video message',
                    'forceShowPushContent'=>1
                ))
            );
            $this->sendSystemMessage($content);
        }

    }

    public function person($senderId , $videoUrl , $firstFrameUrl , $targetId)
    {
        $sender = app(UserRepository::class)->findByUserId(intval($senderId))->toArray();
        $content = array(
            'content'=>'video message',
            'user'=> array(
                'id'=>$sender['user_id'],
                'name'=>$sender['user_nick_name'],
                'portrait'=>$sender['user_avatar_link'],
                'extra'=>array(
                    'userLevel'=>$sender['user_level']
                ),
            ),
            'videoUrl'=>$videoUrl,
            'firstFrameUrl'=>$firstFrameUrl,
            'videoPath'=>'',
            'firstFramePath'=>'',
        );
        $content = array(
            'senderId'   => $senderId,
            'targetId'   => $targetId,
            "objectName" => "Helloo:VideoMsg",
            'content'    => \json_encode($content),
            'pushContent'=>'video message',
            'pushExt'=>\json_encode(array(
                'title'=>'video message',
                'forceShowPushContent'=>1
            ))
        );
        $this->sendPersonMessage($content);

    }

    private function sendSystemPersonMessage($content)
    {
        Log::info('push_system_person_request' , $content);
        $result = app('rcloud')->getMessage()->System()->send($content);
        Log::info('push_system_person_result' , $result);
    }

    private function sendSystemMessage($content)
    {
        Log::info('push_system_request' , $content);
        $result = app('rcloud')->getMessage()->System()->broadcast($content);
        Log::info('push_system_result' , $result);
    }

    private function sendPersonMessage($content)
    {
        Log::info('push_person_request' , $content);
        $result = app('rcloud')->getMessage()->Person()->send($content);
        Log::info('push_person_result' , $result);
    }


}
