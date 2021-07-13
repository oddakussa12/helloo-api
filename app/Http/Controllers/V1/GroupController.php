<?php

namespace App\Http\Controllers\V1;


use App\Models\Group;
use App\Jobs\GroupCreate;
use App\Jobs\GroupUpdate;
use App\Jobs\GroupDestroy;
use App\Models\GroupMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\Paginator;
use App\Resources\AnonymousCollection;
use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Database\Concerns\BuildsQueries;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;

class GroupController extends BaseController
{
    use BuildsQueries;

    /**
     * @note 我的群
     * @datetime 2021-07-12 18:54
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function my(Request $request)
    {
        $userId  = auth()->id();
        $members = GroupMember::where('user_id', $userId)->paginate(50 , array('group_id'));
        $ids     = $members->pluck('group_id')->unique()->values()->toArray();
        $groups  = Group::where('is_deleted' , 0)->whereIn('id', $ids)->get();
        $groups = $this->paginator($groups, $members->total(), 50 , $members->currentPage(), [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
        return AnonymousCollection::collection($groups);
    }

    /**
     * @note 群创建
     * @datetime 2021-07-12 18:55
     * @param StoreGroupRequest $request
     * @return AnonymousCollection|void
     */
    public function store(StoreGroupRequest $request)
    {
        $memberIds = $request->input('user_id' , '');
        $user    = auth()->user();
        $userId  = $user->user_id;
        $now     = date('Y-m-d H:i:s');
        $groupId = app('snowflake')->id();
        $users   = app(UserRepository::class)->findByUserIds($memberIds);
        $users   = $users->reject(function($user){
            return blank($user);
        });

        $memberIds  = $users->pluck('user_id')->toArray();
        if(empty($memberIds))
        {
            return $this->response->errorBadRequest();
        }

        $ids  = collect(array_merge($memberIds , [$userId]))->unique()->values()->toArray();
        $memberData = collect($ids)->map(function($memberId) use ($groupId , $userId , $now){
            return array('user_id'=>$memberId , 'group_id'=>$groupId , 'role'=>intval($userId==$memberId) , 'created_at'=>$now , 'updated_at'=>$now);
        })->toArray();

        $members = collect(array_merge($memberIds , array($userId)))->map(function($memberId){
            return array('id'=>$memberId);
        })->toArray();

        $names   = $users->pluck('user_nick_name' , 'user_id')->toArray();
        $names = array($userId=>$user->user_nick_name)+$names;
        $names   = array_slice($names,0, 4 , true);

        $avatars = $users->pluck('user_avatar_link' , 'user_id')->toArray();
        $avatars = array($userId=>$user->user_avatar)+$avatars;
        $avatars = collect($avatars)->map(function($avatar , $userId){
            return splitJointQnImageUrl($avatar);
        })->toArray();
        $avatars   = array_slice($avatars,0, 4 , true);
        $name = is_array($names)?implode(',' , $names):strval($names);
        DB::beginTransaction();
        try {
            $insert = [
                'id'=>$groupId,
                'user_id'=>$userId,
                'administrator'=>$userId,
                'name'=> \json_encode($names),
                'avatar'=>\json_encode($avatars),
                'member'=>count($ids),
                'created_at'=>$now,
                'updated_at'=>$now
            ];
            $groupResult = DB::table('groups')->insert($insert);
            $groupMembersResult = DB::table('groups_members')->insert($memberData);
            !$groupResult        && abort(405 , 'Group creation failed!');
            !$groupMembersResult && abort(405 , 'Group members creation failed!');
            $result = app('rcloud')->getGroup()->create(array(
                'id'      => $groupId,
                'name'    => $name,
                "members" => $members,
            ));
            $result['code']!=200 && abort(405 , 'RY Group creation failed!');
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::info('group_create_fail' , array(
                'user_id'=>$userId,
                'name'=>$names,
                'memberIds'=>$memberIds,
                'message'=>$exception->getMessage()
            ));
            throw new StoreResourceFailedException('Group creation failed');
        }
        $group = Group::where('id' , $groupId)->where('is_deleted' , 0)->first();
        GroupCreate::dispatch($group , $user , $memberIds)->onQueue('helloo_{group_operate}');
        return new AnonymousCollection($group);
    }

    /**
     * @note 群信息
     * @datetime 2021-07-12 18:55
     * @param $id
     * @return AnonymousCollection|void
     */
    public function show($id)
    {
        $group = Group::where('id' , $id)->where('is_deleted' , 0)->first();
        if(empty($group))
        {
            return $this->response->errorNotFound('Sorry, this group was not found!');
        }
        return new AnonymousCollection($group);
    }

    /**
     * @note 群更新
     * @datetime 2021-07-12 18:55
     * @param UpdateGroupRequest $request
     * @param $id
     * @return \Dingo\Api\Http\Response|void
     */
    public function update(UpdateGroupRequest $request , $id)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $group = Group::where('id' , $id)->where('is_deleted' , 0)->first();
        if(empty($group)||$group->administrator!=$userId)
        {
            return $this->response->errorNotFound('Sorry, this group was not found!');
        }
        $data = array('updated_at'=>date('Y-m-d H:i:s'));
        $name = strval($request->input('name' , ''));
        $avatar = strval($request->input('avatar' , ''));
        if(!empty($name))
        {
            $data['name']=$name;
            $data['name_isset']=1;
        }
        if(!empty($avatar))
        {
            $data['avatar']=$avatar;
            $data['avatar_isset']=1;
        }
        if(!empty($data))
        {
            DB::beginTransaction();
            try{
                $groupResult = DB::table('groups')->where('id' , $id)->update($data);
                !$groupResult && abort(405 , 'Group update failed!');
                if(isset($data['name']))
                {
                    $result = app('rcloud')->getGroup()->update(array(
                        'id'      => $id,
                        'name'    => substr($name , 0 , 60),
                    ));
                    Log::info('$result' , $result);
                    $result['code']!=200 && abort(405 , 'RY Group update failed!');
                }
                DB::commit();
            }catch (\Exception $e)
            {
                DB::rollBack();
                Log::info('group_update_fail' , array(
                    'user_id'=>$userId,
                    'id'=>$id,
                    'message'=>$e->getMessage()
                ));
                throw new UpdateResourceFailedException('Group update failed');
            }
            GroupUpdate::dispatch($group , $user)->onQueue('helloo_{group_operate}');
        }
        return $this->response->accepted();
    }

    /**
     * @note 群解散
     * @datetime 2021-07-12 18:55
     * @param $id
     * @return \Dingo\Api\Http\Response|void
     */
    public function destroy($id)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $group = Group::where('id' , $id)->where('is_deleted' , 0)->first();
        if(empty($group)||$group->administrator!=$userId)
        {
            return $this->response->errorNotFound('Sorry, this group was not found!');
        }
        $now = date('Y-m-d H:i:s');
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
        GroupDestroy::dispatch($group , $user)->onQueue('helloo_{group_operate}');
        return $this->response->noContent();
    }
}
