<?php

namespace App\Jobs;

use App\Models\Group;
use Illuminate\Bus\Queueable;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Repositories\Contracts\UserRepository;

class GroupMemberJoin implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $group;
    private $userIds;
    private $user;
    private $now;

    public function __construct($group , $user , $userIds)
    {
        $this->group = $group;
        $this->user = $user;
        $this->userIds = $userIds;
        $this->now = date("Y-m-d H:i:s");
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
            'content'=>'Group member join',
            'user'=> array(
                'id'=>$senderId,
                'name'=>$operatorNickname,
                'portrait'=>splitJointQnImageUrl($this->user->user_avatar),
                'extra'=>array(
                    'userLevel'=>$this->user->user_level
                ),
            ),
            'operation'=>'group_member_join',
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
            'pushContent'=>'Group member join',
            'pushExt'=>\json_encode(array(
                'title'=>'Group member join',
                'forceShowPushContent'=>1
            ))
        );
        Log::info('group_notice_request' , $content);
        $result = app('rcloud')->getMessage()->Group()->send($content);
        Log::info('group_notice_result' , $result);

        if($this->group->name_isset==0)
        {
            $names = $this->group->getOriginal('name');
            $names = \json_decode($names , true);
            if(is_array($names))
            {
                if(count($names)<4)
                {
                    $joinNames = array_slice(collect($members)->pluck('user_nick_name' , 'user_id')->toArray() , 0 , 4-count($names) , true);
                    $names = $names+$joinNames;
                    DB::table('groups')->where('id' , $groupId)->update(array(
                        'name'=>\json_encode($names),
                        'updated_at'=>$this->now,
                    ));
                    $group = Group::where('id' , $groupId)->first();
                    GroupUpdate::dispatch($group , $this->user)->onQueue('helloo_{group_operate}');
                }
            }

        }
        if($this->group->avatar_isset==0)
        {
            $avatars = $this->group->getOriginal('avatar');
            $avatars = \json_decode($avatars , true);
            if(is_array($avatars))
            {
                if(count($avatars)<4)
                {
                    $joinAvatars = array_slice(collect($members)->pluck('user_avatar_link' , 'user_id')->toArray() , 0 , 4-count($avatars) , true);
                    $avatars = $avatars+$joinAvatars;
                    DB::table('groups')->where('id' , $groupId)->update(array(
                        'avatar'=>\json_encode($avatars),
                        'updated_at'=>$this->now,
                    ));
                    $group = Group::where('id' , $groupId)->first();
                    GroupUpdate::dispatch($group , $this->user)->onQueue('helloo_{group_operate}');
                }
            }
        }
    }

}
