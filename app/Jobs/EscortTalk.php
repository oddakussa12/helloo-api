<?php

namespace App\Jobs;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Repositories\Contracts\UserRepository;

class EscortTalk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $key = "helloo:account:service:account:escort-talker";
        $escortPerson = Redis::smembers($key);
        if(blank($escortPerson))
        {
            return;
        }
        $data = array();
        $senderId = $escortPerson[array_rand($escortPerson)];
        $sender = app(UserRepository::class)->findByUserId($senderId);
        $targetId = $this->user->getKey();
        $cartedAt = Carbon::now()->toDateTimeString();
        $user = DB::table('users_friends')->where('user_id' , $senderId )->where('friend_id' , $targetId)->first();
        if(blank($user))
        {
            array_push($data , array('user_id'=>$senderId , 'friend_id'=>$targetId , 'created_at'=>$cartedAt));
        }
        $target = DB::table('users_friends')->where('user_id' , $targetId )->where('friend_id' , $senderId)->first();
        if(blank($target))
        {
            array_push($data , array('user_id'=>$targetId , 'friend_id'=>$senderId , 'created_at'=>$cartedAt));
        }
        !blank($data)&&DB::table('users_friends')->insert($data);
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
            'videoUrl'=>'https://test.media.helloo.mantouhealth.com/test/3011b3cc07ca6ceac66c8b76a72eba72.mp4',
            'firstFrameUrl'=>'https://test.media.helloo.mantouhealth.com/test/3011b3cc07ca6ceac66c8b76a72eba72.jpg',
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
        Log::info('escort_talk_content' , $content);
        $result = app('rcloud')->getMessage()->Person()->send($content);
        Log::info('escort_talk_result' , $result);
    }

}
