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
    private $extend;

    public function __construct(User $user , $extend)
    {
        $this->user = $user;
        $this->extend = $extend;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = array();
        $targetId = $this->user->getKey();
        $extend = $this->extend;
        $phoneCountry = $extend['user_phone_country'];
        if(in_array($phoneCountry , array(1 , 670 , '1' , '670' , 62 , '62')))
        {
            $talkers = $this->getEscortTalkerByCountry($phoneCountry);
        }else{
            if($phoneCountry==230)
            {
                $cartedAt = Carbon::now()->timestamp;
                $user = DB::table('users_friends')->where('user_id' , 1264078700)->where('friend_id' , $targetId)->first();
                if(blank($user))
                {
                    array_push($data , array('user_id'=>1264078700 , 'friend_id'=>$targetId , 'created_at'=>$cartedAt));
                }
                $target = DB::table('users_friends')->where('user_id' , $targetId )->where('friend_id' , 1264078700)->first();
                if(blank($target))
                {
                    array_push($data , array('user_id'=>$targetId , 'friend_id'=>1264078700 , 'created_at'=>$cartedAt));
                }
                !blank($data)&&DB::table('users_friends')->insert($data);
            }
            return;
        }
        $talker = collect($talkers[array_rand($talkers)])->toArray();
        $senderId = $talker['user_id'];
        $sender = app(UserRepository::class)->findByUserId($senderId);
        $cartedAt = Carbon::now()->timestamp;
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
            'videoUrl'=>$talker['videoUrl'],
            'firstFrameUrl'=>$talker['imageUrl'],
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

    private function getEscortTalkerByCountry($country)
    {
        $key = "helloo:account:service:account:escort-talkers-country-".$country;
        if(Redis::exists($key))
        {
            $talkData = \json_decode(Redis::get($key) , true);
        }else{
            $talkData = DB::table('escort_talker')->where('phone_country' , $country)->select('user_id' , DB::raw('cover as imageUrl') , DB::raw('video as videoUrl'))->get()->toArray();
            Redis::set($key , \json_encode($talkData));
            Redis::expire($key , 60*60*24);
        }
        return $talkData;

    }

}
