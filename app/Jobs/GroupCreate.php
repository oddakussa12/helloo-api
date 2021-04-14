<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class GroupCreate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $group;
    private $userIds;
    private $user;

    public function __construct($group , $user , $userIds)
    {
        $this->group = $group;
        $this->user = $user;
        $this->userIds = $userIds;
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
        $data = array(
            'senderId'      => $senderId,
            'targetId'      => $groupId,
            'objectName'    => 'RC:GrpNtf',
            'content'=>[
                'operatorUserId'=>$senderId,
                'operation'=>'Create',
                'data'=>[
                    'operatorNickname'=>$operatorNickname,
                    'targetGroupName'=>$groupName
                ],
                'message'=>'You have been pulled into the group',
                'extra'=>[

                ],
            ]
        );
        Log::info('group_notice_request' , $data);
        $result = app('rcloud')->getMessage()->getGroup()->send($data);
        Log::info('group_notice_result' , $result);
    }

}
