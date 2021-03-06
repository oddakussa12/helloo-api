<?php

namespace App\Http\Controllers\V1;


use App\Models\Group;
use App\Jobs\GroupDestroy;
use App\Models\GroupMember;
use Illuminate\Http\Request;
use App\Jobs\GroupMemberJoin;
use App\Jobs\GroupMemberExit;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Repositories\Contracts\UserRepository;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;

class GroupMemberController extends BaseController
{
    /**
     * @note 群成员
     * @datetime 2021-07-12 18:55
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|void
     */
    public function index(Request $request)
    {
        $userId = auth()->id();
        $groupId = intval($request->input('group_id' , 0));
        $group = Group::where('id' , $groupId)->where('is_deleted' , 0)->first();
        if(empty($group))
        {
            return $this->response->errorNotFound('Sorry, this group was not found!');
        }
        $groupMember = GroupMember::where('group_id' , $groupId)->get();
        $memberIds = $groupMember->pluck('user_id')->toArray();
        if(empty($memberIds)||!in_array($userId , $memberIds))
        {
            return $this->response->errorNotFound('Sorry, you did not join this group!');
        }
        $users = app(UserRepository::class)->findByUserIds($memberIds);
        return UserCollection::collection($users);
    }

    /**
     * @note 群成员更新
     * @datetime 2021-07-12 18:56
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function update(Request $request)
    {
        $type = $request->input('type' , '');
        if(in_array($type , array('exit' , 'kick')))
        {
            $this->$type($request);
        }
        return $this->response->accepted();
    }

    /**
     * @note 群成员退出
     * @datetime 2021-07-12 18:56
     * @param Request $request
     * @return \Dingo\Api\Http\Response|void
     */
    private function exit(Request $request)
    {
        $id = strval($request->input('group_id' , 0));
        $user = auth()->user();
        $userId = $user->user_id;
        $group = Group::where('id' , $id)->where('is_deleted' , 0)->first();
        if(empty($group))
        {
            return $this->response->errorNotFound('Sorry, this group was not found!');
        }
        $now = date('Y-m-d H:i:s');
        $groupMember = GroupMember::where('group_id' , $id)->where('user_id' , $userId)->first();
        if(empty($groupMember))
        {
            return $this->response->errorNotFound('Sorry, you have left this group!');
        }
        $groupMember = $groupMember->makeVisible(array('id'))->toArray();
        if($group->administrator==$userId)
        {
            $groupMembers = DB::table('groups_members')->where('group_id' , $id)->select(array(
                'user_id',
                'group_id',
                'role',
                'created_at',
                'updated_at',
            ))->get()->map(function ($value) {return (array)$value;})->toArray();
            $groupMembers = collect($groupMembers)->each(function($groupMember , $index) use ($now){
                $groupMember['deleted_at'] = $now;
                return $groupMember;
            })->toArray();
            DB::beginTransaction();
            try{
                $groupResult = DB::table('groups')->where('id' , $id)->update(array(
                    'is_deleted'=>1,
                    'deleted_at'=>$now,
                ));
                $groupMemberResult = DB::table('groups_members')->where('group_id' , $id)->delete();
                $insertMemberLogResult = DB::table('groups_members_logs')->insert($groupMembers);
                !$groupResult && abort(405 , 'Group update failed!');
                !$groupMemberResult && abort(405 , 'Group member delete failed!');
                !$insertMemberLogResult && abort(405 , 'Group member log insert failed!');
                $result = app('rcloud')->getGroup()->dismiss([
                    'id'=>$id, 'member'=>['id'=>$userId]
                ]);
                $result['code']!=200 && abort(405 , 'RY Group dismiss failed!');
                DB::commit();
            }catch (\Exception $exception){
                DB::rollBack();
                Log::info('group_dismiss_fail' , array(
                    'user_id'=>$userId,
                    'message'=>$exception->getMessage()
                ));
                throw new UpdateResourceFailedException('Group dismiss failed');
            }
//            GroupDestroy::dispatch($group , $user)->onQueue('helloo_{group_operate}');
        }else{
            $groupMember['deleted_at'] = $now;
            $groupData = array('member'=>DB::raw('member-1') ,  'updated_at'=>$now);
            DB::beginTransaction();
            try{
                $deleteMemberResult = DB::table('groups_members')->where('id' , $groupMember['id'])->delete();
                unset($groupMember['id']);
                $insertMemberLogResult = DB::table('groups_members_logs')->insert($groupMember);
                $updateGroupResult = DB::table('groups')->where('id' , $id)->update($groupData);
                !$deleteMemberResult&&abort(405 , 'Group member delete failed!');
                !$insertMemberLogResult&&abort(405 , 'Group member log insert failed!');
                !$updateGroupResult&&abort(405 , 'Group update failed!');
                $result = app('rcloud')->getGroup()->quit(array(
                    'id'      => $id,
                    "member" => array(
                        'id'=>array($userId)
                    ),
                ));
                $result['code']!=200&&abort(405 , 'RY Group quit failed!');
                DB::commit();
            }catch (\Exception $exception)
            {
                DB::rollBack();
                Log::info('group_quit_fail' , array(
                    'id'=>$id,
                    'user_id'=>$userId,
                    'message'=>$exception->getMessage()
                ));
                throw new UpdateResourceFailedException('Group quit failed');
            }
//            GroupMemberExit::dispatch($group , $user , [$userId] , 'exit')->onQueue('helloo_{group_member_update}');
        }
        return $this->response->accepted();
    }

    /**
     * @note 群成员剔出
     * @datetime 2021-07-12 18:56
     * @param Request $request
     * @return \Dingo\Api\Http\Response|void
     */
    private function kick(Request $request)
    {
        $id = strval($request->input('group_id' , 0));
        $user = auth()->user();
        $userId = $user->user_id;
        $kickedUserIds =(array)(request()->input('user_id' , array()));
        if(count($kickedUserIds)<=0)
        {
            return $this->response->errorNotFound('Sorry, this user was not found!');
        }
        $group = Group::where('id' , $id)->where('is_deleted' , 0)->first();
        if(empty($group))
        {
            return $this->response->errorNotFound('Sorry, this group was not found!');
        }
        if($group->administrator!=$userId)
        {
            return $this->response->errorForbidden('Sorry, you do not have permission to delete others!');
        }
        $now = date('Y-m-d H:i:s');
        $groupAdmin = GroupMember::where('group_id' , $id)->where('user_id' , $userId)->first();
        if(empty($groupAdmin))
        {
            return $this->response->errorNotFound('Sorry, you have left this group!');
        }
        $groupAdmin = $groupAdmin->makeVisible(array('id'))->toArray();
        if($groupAdmin['role']!=1)
        {
            return $this->response->errorForbidden('Sorry, you do not have permission to delete others!');
        }
        $groupMembers = GroupMember::where('group_id', $id)->whereIn('user_id' , $kickedUserIds)->get();
        if(blank($groupMembers))
        {
            return $this->response->errorNotFound('Sorry, this user has left this group!');
        }
        $groupMemberIds = $groupMembers->pluck('user_id')->toArray();
        $memberCount = count($groupMemberIds);
        $groupData = array('member'=>DB::raw("member-$memberCount") ,  'updated_at'=>$now);
        $groupMemberData = $groupMembers->toArray();
        $groupMemberData = collect($groupMemberData)->each(function ($groupMember , $index) use ($now){
            $groupMember['deleted_at'] = $now;
            return $groupMember;
        })->toArray();
        $members = collect($groupMemberIds)->map(function($groupMemberId){
            return $groupMemberId;
        })->toArray();
        DB::beginTransaction();
        try{
            $deleteMemberResult = DB::table('groups_members')->where('group_id', $id)->whereIn('user_id' , $groupMemberIds)->delete();
            $insertMemberLogResult = DB::table('groups_members_logs')->insert($groupMemberData);
            $updateGroupResult = DB::table('groups')->where('id' , $id)->update($groupData);
            !$deleteMemberResult&&abort(405 , 'Group member delete failed!');
            !$insertMemberLogResult&&abort(405 , 'Group member log insert failed!');
            !$updateGroupResult&&abort(405 , 'Group update failed!');
            $result = app('rcloud')->getGroup()->quit(array(
                'id'      => $id,
                "member" => array('id'=>$members),
            ));
            $result['code']!=200&&abort(405 , 'RY Group kick failed!');
            DB::commit();
        }catch (\Exception $exception)
        {
            DB::rollBack();
            Log::info('group_kick_fail' , array(
                'id'=>$id,
                'user_id'=>$userId,
                'members'=>$members,
                'message'=>$exception->getMessage()
            ));
            throw new UpdateResourceFailedException('Group kick failed');
        }
//        GroupMemberExit::dispatch($group , $user , $groupMemberIds , 'kicked')->onQueue('helloo_{group_member_update}');
        return $this->response->accepted();
    }

    /**
     * @note 群成员加入
     * @datetime 2021-07-12 18:56
     * @param Request $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function join(Request $request)
    {
        $type = strval($request->input('type' , 'join'));
        $id = strval($request->input('group_id' , 0));
        $user = auth()->user();
        $auth = $user->user_id;
        $group = Group::where('id' , $id)->where('is_deleted' , 0)->first();
        if(empty($group))
        {
            return $this->response->errorNotFound('Sorry, this group was not found!');
        }
        $now = date('Y-m-d H:i:s');
        if($type=='join')
        {
            $member = DB::table('groups_members')->where('group_id' , $id)->where('user_id' ,$auth)->first();
            if(blank($member))
            {
                DB::beginTransaction();
                try{
                    $groupMemberResult = DB::table('groups_members')->insert(array(
                        'group_id'=>$id,
                        'user_id'=>$auth,
                        'created_at'=>$now,
                        'updated_at'=>$now,
                    ));
                    !$groupMemberResult && abort(405 , 'Group member join failed!');
                    $groupData = array('member'=>DB::raw("member+1") ,  'updated_at'=>$now);
                    $groupResult = DB::table('groups')->where('id' , $id)->update($groupData);
                    !$groupResult        && abort(405 , 'Group pull join failed!');
                    $result = app('rcloud')->getGroup()->joins(array(
                        'id'      => $id,
                        'name'    => $group->name,
                        'member'=>array(
                            'id'=> $auth
                        )
                    ));
                    $result['code']!=200 && abort(405 , 'RY Group join failed!');
                    DB::commit();
                }catch (\Exception $e)
                {
                    DB::rollBack();
                    Log::info('group_join_fail' , array(
                        'user_id'=>$auth,
                        'id'=>$id,
                        'message'=>$e->getMessage()
                    ));
                    throw new UpdateResourceFailedException('Group join failed!');
                }
//                GroupMemberJoin::dispatch($group , $user , [$auth])->onQueue('helloo_{group_member_update}');
            }
        }else if($type=='pull'){
            $userIds = (array)$request->input('user_id' , array());
            if(empty($userIds))
            {
                return $this->response->accepted();
            }
            $members = DB::table('groups_members')->where('group_id' , $id)->whereIn('user_id' ,$userIds)->get();
            $memberIds = $members->pluck('user_id')->toArray();
            $userIds = array_diff($userIds , $memberIds);
            if(!empty($userIds))
            {
                $memberData = collect($userIds)->map(function($memberId) use ($id , $now){
                    return array('user_id'=>$memberId , 'group_id'=>$id , 'created_at'=>$now , 'updated_at'=>$now);
                })->toArray();
                $memberCount = count($memberData);
                $groupData = array('member'=>DB::raw("member+$memberCount") ,  'updated_at'=>$now);
                DB::beginTransaction();
                try{
                    $groupMemberResult = DB::table('groups_members')->insert($memberData);
                    !$groupMemberResult && abort(405 , 'Group member pull join failed!');
                    $groupResult = DB::table('groups')->where('id' , $id)->update($groupData);
                    !$groupResult        && abort(405 , 'Group pull join failed!');
                    $result = app('rcloud')->getGroup()->joins(array(
                        'id'      => $id,
                        'name'    => $group->name,
                        'member'=>array(
                            'id'=> $userIds
                        )
                    ));
                    $result['code']!=200 && abort(405 , 'RY Group pull join failed!');
                    DB::commit();
                }catch (\Exception $e)
                {
                    DB::rollBack();
                    Log::info('group_request_join_fail' , array(
                        'user_id'=>$userIds,
                        'id'=>$id,
                        'message'=>$e->getMessage()
                    ));
                    throw new UpdateResourceFailedException('Group pull join failed!');
                }
//                GroupMemberJoin::dispatch($group , $user , $userIds)->onQueue('helloo_{group_member_update}');
            }
        }
        return $this->response->accepted();
    }
}
