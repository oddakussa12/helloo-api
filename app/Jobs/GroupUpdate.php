<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GroupUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $group;
    private $user;

    public function __construct($group , $user)
    {
        $this->group = $group;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $senderId = $this->user->user_id;
        $operatorNickname = $this->user->user_nick_name;
        $groupId = $this->group->id;
        $groupName = $this->group->name;
        $groupAvatar = $this->group->avatar;
        $content = array(
            'content'=>'Group update',
            'user'=> array(
                'id'=>$senderId,
                'name'=>$operatorNickname,
                'portrait'=>userCover($this->user->user_avatar),
                'extra'=>array(
                    'userLevel'=>$this->user->user_level
                ),
            ),
            'operation'=>'group_update',
            'data'=>array(
                'groupName'=>$groupName,
                'groupAvatar'=>$groupAvatar,
            )
        );
        $content = array(
            'senderId'   => $senderId,
            "objectName" => "RC:GrpNtf",
            'targetId'      => $groupId,
            'content'    => \json_encode($content),
            'pushContent'=>'Group update',
            'pushExt'=>\json_encode(array(
                'title'=>'Group update',
                'forceShowPushContent'=>1
            ))
        );
        Log::info('group_notice_request' , $content);
        $result = app('rcloud')->getMessage()->Group()->send($content);
        Log::info('group_notice_result' , $result);
    }

}
