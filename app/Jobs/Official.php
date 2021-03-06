<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Repositories\Contracts\UserRepository;

class Official implements ShouldQueue
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
        $sender = app(UserRepository::class)->findByUserId(64)->toArray();
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
                'videoUrl'=>'https://test.video.helloo.mantouhealth.com/38af86134b65d0f10fe33d30dd76442e/20210107/t.mp4',
                'videoPath'=>'',
                'firstFrameUrl'=>'https://qnidyooulimage.mmantou.cn/FisdVkCRfoLDT3bOCfi9XLX8XWpu.png?imageView2/5/w/192/h/192/interlace/1|imageslim',
                'firstFramePath'=>'',
            ),
            'pushContent'=>'video message',
            'pushExt'=>\json_encode(array(
                'title'=>'video message',
                'forceShowPushContent'=>1
            ))
        );
        Log::info('escort_talk_content' , $content);
        $result = app('rcloud')->getMessage()->System()->send($content);
        Log::info('escort_talk_result' , $result);
    }

}
