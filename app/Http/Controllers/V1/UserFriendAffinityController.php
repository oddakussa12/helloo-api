<?php

namespace App\Http\Controllers\V1;

use App\Custom\Constant\Constant;
use App\Jobs\Affinity;
use App\Jobs\Friend;
use App\Models\UserFriend;
use App\Models\UserFriendLevel;
use App\Models\UserFriendRelationship;
use Illuminate\Http\Request;
use App\Resources\UserCollection;
use App\Resources\UserFriendCollection;
use App\Repositories\Contracts\UserRepository;
use App\Http\Requests\StoreUserFriendRequestRequest;
use App\Repositories\Contracts\UserFriendRequestRepository;

/**
 * Class UserFriendAffinityController
 * @package App\Http\Controllers\V1
 * 特殊好友关系
 */
class UserFriendAffinityController extends BaseController
{

    public function __construct()
    {
    }
    /*public function paginateByUser($toId , $perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $pageName = isset($this->model->paginateParamName)?$this->model->paginateParamName:$pageName;
        return $this->model->where('request_to_id' , $toId)->orderBy($this->model->getCreatedAtColumn(), 'DESC')->paginate($perPage , $columns , $pageName , $page);
    }*/

    /**
     * @return mixed
     * 特殊关系列表
     */
    public function index()
    {
       return UserFriendRelationship::select('id','name','alias_name')->where('is_delete', 0)->orderBy('sort', 'ASC')->get();
    }

    public function rule()
    {


    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function list(Request $request)
    {
        $page   = $request->input('page', 1);
        $page   = intval($page)>0 ? intval($page) : 1;
        $status = $request->input('status', 1);
        $userId = auth()->id();
        $result = UserFriendLevel::where('user_id', $userId)->orWhere('friend_id', $userId)->where('is_delete', 0)
            ->where('status', $status)->orderBy('created_at', 'DESC')->paginate(10, ['*'], 'page', $page);

        $userIds   = $result->pluck('user_id')->all();
        $friendIds = $result->pluck('friend_id')->all();
        $friendIds = array_unique(array_merge($userIds, $friendIds));
        $friendIds = array_filter($friendIds,
            function($value) use ($userId) {if (!empty($value) && $value != $userId) return $value;
        });
        $users     = app(UserRepository::class)->findByMany($friendIds);

        //return $result;

        foreach ($result as $index=>$value) {
            $user_id = $value['user_id'] == $userId ? $value['friend_id'] : $value['user_id'];
            $value['friend'] = $users->where('user_id', $user_id)->first();
            $result[$index]  = $value;
        }

        return $result;
        dump($friendIds, $result);

        /*$friends = app(UserRepository::class)->findByMany($friendIds);
        $userFriendRequests = $result->filter(function ($value, $key) {
            return !blank($value->friend);
        });
        return UserFriendCollection::collection($userFriendRequests);*/
    }

    /**
     * @param StoreUserFriendRequestRequest $request
     * @return mixed
     */
    public function store(StoreUserFriendRequestRequest $request)
    {

        $friendId    = intval($request->input('friend_id'));
        $relation_id = intval($request->input('relationship_id'));
        $this->validate($request, [
            'relationship_id' => 'required|int',
            'friend_id'       => 'required|int',
        ]);

        $requests    = new UserFriendLevel();
        $auth        = auth()->user();
        $arr         = [$auth->user_id, $friendId];
        sort($arr);

        $relation    = UserFriendRelationship::where(['is_delete'=>0,'id'=>$relation_id])->first();
        if (empty($relation)) {
            return $this->response->noContent();
            return $this->response->errorNotFound('该关系不存在');
        }

        list($userId, $friendId)   = $arr;
        $requests->user_id         = $userId;
        $requests->friend_id       = $friendId;
        $requests->relationship_id = $relation_id;
        $requests->save();
        $user = new UserCollection($auth);
        $user->extra = array(
            'devicePlatformName'=>'Server'
        );

        // 融云推送 聊天
        $this->dispatch((new Friend($auth->user_id, $friendId, 'Yooul:AffinityFriendRequest', [
            'content' => 'friend request',
            'user'    => $user
        ]))->onQueue('friend'));

        // 推送通知
        $this->dispatch((new Affinity($user, $requests, 'friend_request'))->onQueue(Constant::QUEUE_PUSH_FRIEND));
        return $this->response->created();
    }

    /**
     * @param $friendId
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     * 接受特殊关系请求
     */
    public function accept($friendId, Request $request)
    {
        $user   = auth()->user();
        $userId = $user->user_id;
        $arr    = [$user->user_id, $friendId];
        sort($arr);
        list($user_id, $friendId) = $arr;

        $userFriend = UserFriend::where('user_id', $userId)->where('friend_id' , $friendId)->first();
        $friendUser = UserFriend::where('user_id', $friendId)->where('friend_id' , $userId)->first();

        if (empty($userFriend) || empty($friendUser)) {
            return $this->response->errorNotFound();
        }

        UserFriendLevel::where(['user_id'=>$user_id,'friend_id'=>$friendId, 'is_delete'=>0, 'status'=>0])->update(['status'=>1]);

        $user = new UserCollection($user);
        $user->extra = array(
            'devicePlatformName'=>'Server'
        );


        // 融云推送 聊天
        $this->dispatch((new Friend($userId, $friendId, 'Yooul:AffinityFriendRequestReposed', [
            'content' => 'friend response' ,
            'reposed' => 1,
            'user'    => $user
        ]))->onQueue('friend'));

        // 推送通知
        $this->dispatch((new Affinity($user, $request->all(), 'accept_friend_request'))->onQueue(Constant::QUEUE_PUSH_FRIEND));

        return $this->response->accepted();
    }

    /**
     * @param $friendId
     * @return \Dingo\Api\Http\Response
     * 拒绝/忽略邀请
     */
    public function refuse($friendId)
    {
        $auth = auth()->user();
        $arr  = [$auth->user_id, $friendId];
        sort($arr);
        list($user_id, $friendId) = $arr;

        UserFriendLevel::where(['user_id'=>$user_id,'friend_id'=>$friendId, 'is_delete'=>0, 'status'=>0])->update(['status'=>-1,'is_delete'=>-1]);
        return $this->response->accepted();
    }
}
