<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\EventRepository;

class SignUpAndEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $user;
    private $now;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->now = date('Y-m-d H:i:s');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data['user_id']    = $this->user->user_id;
        $data['friend']     = 1;
        $data['video']      = 1;
        $data['photo']      = 1;
        $data['shop']      = 1;
        $data['created_at'] = $this->now;
        $data['updated_at'] = $this->now;
        DB::table('users_settings')->insert($data);
        $cache = collect($data)->only('friend', 'video', 'photo' , 'post')->toArray();
        $key   = 'helloo:account:service:account-personal-privacy:'.$this->user->user_id;
        Redis::set($key, json_encode($cache));
        Redis::expire($key , 86400);
        return;
        $now = Carbon::now()->timestamp;
        $activeEvents = app(EventRepository::class)->getActiveEvent();
        Log::info('$activeEvents' , array($activeEvents));
        if(!blank($activeEvents))
        {
            $key = "helloo:account:system:senderId";
            $systemId = Redis::get($key);
            if ($systemId===null) {
                return;
            }
            $sender = app(UserRepository::class)->findByUserId(intval($systemId))->toArray();
            $content = array(
                'senderId'   => $sender['user_id'],
                'targetId'   => $this->user->user_id,
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
                ),
                'pushContent'=>'video message',
                'pushExt'=>\json_encode(array(
                    'title'=>'video message',
                    'forceShowPushContent'=>1
                ))
            );
            foreach ($activeEvents as $activeEvent)
            {
                if($activeEvent['ended_at']<$now)
                {
                    continue;
                }
                $content['content']['firstFrameUrl'] = $activeEvent['image'];
                $content['content']['videoUrl'] = $activeEvent['value'];
                $this->sendEventMessage($content);
            }

            // 写入隐私表
            $data['user_id']    = $sender['user_id'];
            $data['friend']     = 1;
            $data['video']      = 1;
            $data['photo']      = 1;
            $data['shop']      = 1;
            $data['created_at'] = $this->now;
            $data['updated_at'] = $this->now;
            DB::table('users_settings')->insert($data);
            $cache = collect($data)->only('friend', 'video', 'photo' , 'post')->toArray();
            $key   = 'helloo:account:service:account-personal-privacy:'.$this->user->user_id;
            Redis::set($key, json_encode($cache));
            Redis::expire($key , 86400);
        }
    }

    public function sendEventMessage($content)
    {
        Log::info('sign_up_and_event_content' , $content);
        $result = app('rcloud')->getMessage()->System()->send($content);
        Log::info('sign_up_and_event_result' , $result);
    }

}
