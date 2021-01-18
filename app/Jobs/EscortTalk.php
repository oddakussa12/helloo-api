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
//        $key = "helloo:account:service:account:escort-talker";
//        $escortPerson = Redis::smembers($key);
//        if(blank($escortPerson))
//        {
//            return;
//        }
//        $senderId = $escortPerson[array_rand($escortPerson)];
        $data = array();
        $targetId = $this->user->getKey();
//        $phone = DB::table('users_phones')->where('user_id' , $targetId)->first();
        $extend = $this->extend;
        $phoneCountry = $extend['user_phone_country'];
        if(in_array($phoneCountry , array(1 , 670 , '1' , '670' , 62 , '62')))
        {
            $talkers = $this->getEscortTalkerByCountry($phoneCountry);
        }else{
            return;
        }
//        if($phoneCountry==1)
//        {
//            $talkers = array(
//                array(
//                    'user_id'=>297,
//                    'imageUrl'=>'https://image.helloo.mantouhealth.com/d395771085aab05244a4fb8fd91bf4ee/20210116/1610803454416346.jpeg',
//                    'videoUrl'=>'https://video.helloo.mantouhealth.com/d395771085aab05244a4fb8fd91bf4ee/20210116/1610803454416346.mp4',
//                ),
//                array(
//                    'user_id'=>304,
//                    'imageUrl'=>'https://image.helloo.mantouhealth.com/37bc2f75bf1bcfe8450a1a41c200364c/20210116/1610803454514927.jpeg',
//                    'videoUrl'=>'https://video.helloo.mantouhealth.com/37bc2f75bf1bcfe8450a1a41c200364c/20210116/1610803454514927.mp4',
//                ),
//                array(
//                    'user_id'=>59059,
//                    'imageUrl'=>'https://image.helloo.mantouhealth.com/4fae0695454a986d8328aadba1534575/20210116/1610803455151260.jpeg',
//                    'videoUrl'=>'https://video.helloo.mantouhealth.com/4fae0695454a986d8328aadba1534575/20210116/1610803455151260.mp4',
//                ),
//            );
//        }elseif ($phoneCountry==670)
//        {
//            $talkers = array(
//                array(
//                    'user_id'=>268,
//                    'imageUrl'=>'https://image.helloo.mantouhealth.com/8f121ce07d74717e0b1f21d122e04521/20210116/1610805953489110.jpeg',
//                    'videoUrl'=>'https://video.helloo.mantouhealth.com/8f121ce07d74717e0b1f21d122e04521/20210116/1610805953489110.mp4',
//                ),
//                array(
//                    'user_id'=>275,
//                    'imageUrl'=>'https://image.helloo.mantouhealth.com/63923f49e5241343aa7acb6a06a751e7/20210116/1610805950965147.jpeg',
//                    'videoUrl'=>'https://video.helloo.mantouhealth.com/63923f49e5241343aa7acb6a06a751e7/20210116/1610805950965147.mp4',
//                ),
//                array(
//                    'user_id'=>33310,
//                    'imageUrl'=>'https://image.helloo.mantouhealth.com/cc46f33ee83eb94cea644695f8717c0e/20210116/1610805949290366.jpeg',
//                    'videoUrl'=>'https://video.helloo.mantouhealth.com/cc46f33ee83eb94cea644695f8717c0e/20210116/1610805949290366.mp4',
//                ),
//            );
//        }else{
//            return;
//        }
        $talker = $talkers[array_rand($talkers)];
        $senderId = $talker['user_id'];
        $sender = app(UserRepository::class)->findByUserId($senderId);
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
//            'videoUrl'=>'https://test.media.helloo.mantouhealth.com/test/3011b3cc07ca6ceac66c8b76a72eba72.mp4',
//            'firstFrameUrl'=>'https://test.media.helloo.mantouhealth.com/test/3011b3cc07ca6ceac66c8b76a72eba72.jpg',
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
            $talkData = DB::table('escort_talker')->where('phone_country' , $country)->select('user_id' , DB::raw('cover as imageUrl') , DB::raw('video as imageUrl'))->get()->toArray();
            Redis::set($key , \json_encode($talkData));
            Redis::expire($key , 60*60*24);
        }
        return $talkData;

    }

}
