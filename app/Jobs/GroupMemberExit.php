<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Repositories\Contracts\UserRepository;

class GroupMemberExit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $group;
    private $userIds;
    private $user;
    private $type;

    public function __construct($group , $user , $userIds , $type)
    {
        $this->group = $group;
        $this->user = $user;
        $this->userIds = $userIds;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->type='exit')
        {
            $operation = 'group_member_exit';
        }elseif ($this->type='kicked')
        {
            $operation = 'group_member_kicked';
        }else{
            return;
        }
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
            'content'=>'Group member exit',
            'user'=> array(
                'id'=>$senderId,
                'name'=>$operatorNickname,
                'portrait'=>userCover($this->user->user_avatar),
                'extra'=>array(
                    'userLevel'=>$this->user->user_level
                ),
            ),
            'operation'=>$operation,
            'data'=>array(
                'groupName'=>$groupName,
                'members'=>$members,
            )
        );
        $content = array(
            'senderId'   => $senderId,
            "objectName" => "RC:GrpNtf",
            'targetId'      => $groupId,
            'content'    => \json_encode($content),
            'pushContent'=>'Group member exit',
            'pushExt'=>\json_encode(array(
                'title'=>'Group member exit',
                'forceShowPushContent'=>1
            )),
            'isPersisted'
        );
        Log::info('group_notice_request' , $content);
        $result = app('rcloud')->getMessage()->Group()->send($content);
        Log::info('group_notice_result' , $result);

        if($this->group->name_isset==0)
        {
            $names = $this->group->getOriginal('name');
            Log::info('$names' , array($names));
            foreach ($names as $userId=>$name)
            {
                if(in_array($userId , $this->userIds))
                {
                    unset($names[$userId]);
                }
            }
        }
        if($this->group->avatar_isset==0)
        {
            $avatars = $this->group->avatar;
            Log::info('$avatars' , array($avatars));
//            foreach ($avatars as $userId=>$avatar)
//            {
//                if(in_array($userId , $this->userIds))
//                {
//                    unset($avatars[$userId]);
//                }
//            }
        }
    }

}
