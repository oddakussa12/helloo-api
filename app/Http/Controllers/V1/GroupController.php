<?php

namespace App\Http\Controllers\V1;


use App\Models\Group;
use App\Models\GroupMember;
use App\Resources\AnonymousCollection;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;

class GroupController extends BaseController
{
    public function index(Request $request)
    {
        $userId = intval($request->input('user_id' , 0));
    }

    public function my(Request $request)
    {
        $userId = auth()->id();
        $groups = Group::where('administrator' , $userId)->where('is_deleted' , 0)->paginate(50);
        return AnonymousCollection::collection($groups);
    }

    public function store(StoreGroupRequest $request)
    {
        $user   = auth()->user();
        $userId = $user->user_id;
        $name = $request->input('name' , '');
        $memberIds = $request->input('user_id' , '');
        $now = date('Y-m-d H:i:s');
        $groupId = app('snowflake')->id();
        $memberData = collect(array_merge($memberIds , array($userId)))->map(function($memberId) use ($groupId , $userId , $now){
            return array('user_id'=>$memberId , 'group_id'=>$groupId , 'role'=>intval($userId==$memberId) , 'created_at'=>$now , 'updated_at'=>$now);
        })->toArray();
        DB::beginTransaction();
        try {
            $groupResult        = DB::table('groups')->insert(array(
                'id'=>$groupId,
                'user_id'=>$userId,
                'administrator'=>$userId,
                'name'=>$name,
                'member'=>count(array_merge($memberIds , array($userId))),
                'created_at'=>$now,
                'updated_at'=>$now
            ));
            $groupMembersResult = DB::table('groups_members')->insert($memberData);
            !$groupResult        && abort(405 , 'Group creation failed!');
            !$groupMembersResult && abort(405 , 'Group members creation failed!');
            $result = app('rcloud')->getGroup()->create(array(
                'id'      => $groupId,
                'name'    => $name,
                "members" => [['id'=>$userId]],
            ));
            $result['code']!=200 && abort(405 , 'RY Group creation failed!');
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::info('group_create_fail' , array(
                'user_id'=>$userId,
                'name'=>$name,
                'memberIds'=>$memberIds,
                'message'=>$exception->getMessage()
            ));
            throw new StoreResourceFailedException('Group creation failed');
        }
        $group = Group::where('id' , $groupId)->first();
        return new AnonymousCollection($group);
    }

    public function show($id)
    {
        $userId = auth()->id();
        $group = Group::where('id' , $id)->first();
        if(empty($group)||$group->is_deleted==1||$group->administrator!=$userId)
        {
            return $this->response->errorNotFound('Sorry, this group was not found!');
        }
        return new AnonymousCollection($group);
    }

    public function update(UpdateGroupRequest $request , $id)
    {
        $userId = auth()->id();
        $group = Group::where('id' , $id)->first();
        if(empty($group)||$group->is_deleted==1||$group->administrator!=$userId)
        {
            return $this->response->errorNotFound('Sorry, this group was not found!');
        }
        $data = array('updated_at'=>date('Y-m-d H:i:s'));
        $name = strval($request->input('name' , ''));
        $avatar = strval($request->input('avatar' , ''));
        !empty($name)&&$data['name']=$name;
        !empty($avatar)&&$data['avatar']=$avatar;
        if(!empty($data))
        {
            DB::beginTransaction();
            try{
                $groupResult = DB::table('groups')->where('id' , $id)->update($data);
                if(isset($data['name']))
                {
                    $result = app('rcloud')->getGroup()->update(array(
                        'id'      => $id,
                        'name'    => $name,
                    ));
                    $result['code']!=200 && abort(405 , 'RY Group update failed!');
                }
                !$groupResult        && abort(405 , 'Group update failed!');
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
        }
        return $this->response->accepted();
    }

    public function destroy($id)
    {
        $userId = auth()->id();
        $group = Group::where('id' , $id)->first();
        if(empty($group)||$group->is_deleted==1||$group->administrator!=$userId)
        {
            return $this->response->errorNotFound('Sorry, this group was not found!');
        }
        $now = date('Y-m-d H:i:s');
        DB::beginTransaction();
        try{
            $groupResult = DB::table('groups')->where('id' , $id)->update(array(
                'is_deleted'=>1,
                'deleted_at'=>$now,
            ));
            $result = app('rcloud')->getGroup()->dismiss([
                'id'=>$id, 'member'=>['id'=>$userId]
            ]);
            $result['code']!=200 && abort(405 , 'RY Group dismiss failed!');
            !$groupResult        && abort(405 , 'Group dismiss failed!');
            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            Log::info('group_dismiss_fail' , array(
                'user_id'=>$userId,
                'message'=>$exception->getMessage()
            ));
            throw new UpdateResourceFailedException('Group dismiss failed');
        }
        return $this->response->noContent();
    }
}
