<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Repositories\Contracts\UserRepository;

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
        $members = app(UserRepository::class)->findByUserIds($this->userIds);
        $members = $members->reject(function ($member) {
            return blank($member);
        })->values();
        $members = collect(UserCollection::collection($members))->toArray();
        $content = array(
            'content'=>'Group create',
            'user'=> array(
                'id'=>$senderId,
                'name'=>$operatorNickname,
                'portrait'=>splitJointQnImageUrl($this->user->user_avatar),
                'extra'=>array(
                    'userLevel'=>$this->user->user_level
                ),
            ),
            'operation'=>'group_create',
            'data'=>array(
                'groupName'=>$groupName,
                'members'=>$members,
            )
        );
        $content = array(
            'senderId'   => $senderId,
            "objectName" => "Helloo:GroupNotice",
            'targetId'      => $groupId,
            'content'    => \json_encode($content),
            'pushContent'=>'Group create',
            'pushExt'=>\json_encode(array(
                'title'=>'Group create',
                'forceShowPushContent'=>1
            ))
        );
        Log::info('group_notice_request' , $content);
        $result = app('rcloud')->getMessage()->Group()->send($content);
        Log::info('group_notice_result' , $result);
    }

}
