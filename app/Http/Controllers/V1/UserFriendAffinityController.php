<?php

namespace App\Http\Controllers\V1;

use App\Custom\Constant\Constant;
use App\Jobs\Affinity;
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

    /**
     * @var UserFriendRequestRepository
     */
    private $userFriendRequest;


    /**
     * UserFriendController constructor.
     * @param UserFriendRequestRepository $userFriendRequest
     */
    public function __construct(UserFriendRequestRepository $userFriendRequest)
    {
        $this->userFriendRequest = $userFriendRequest;
    }
    /*public function paginateByUser($toId , $perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $pageName = isset($this->model->paginateParamName)?$this->model->paginateParamName:$pageName;
        return $this->model->where('request_to_id' , $toId)->orderBy($this->model->getCreatedAtColumn(), 'DESC')->paginate($perPage , $columns , $pageName , $page);
    }*/

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $page   = $request->input('page', 1);
        $page   = intval($page)>0 ? intval($page) : 1;
        $status = $request->input('status', 1);
        $userId = auth()->id();
        $result = UserFriendLevel::where('user_id', $userId)->orWhere('friend_id', $userId)->where('is_delete', 0)
            ->where('status', $status)->orderBy('created_at', 'DESC')->paginate(10, ['*'], 'page', $page);

        $friendIds = array_filter($result, function ($value) use ($userId) {
            if ($value['user_id'] == $userId) return $value['friend_id'];
            if ($value['friend_id'] == $userId) return $value['user_id'];
        });

        /*$friends = app(UserRepository::class)->findByMany($friendIds);
        $result->each(function($friend , $key) use ($friends){
            $friend->friend = $friends->where('user_id' , $friend->friend_id)->first();
        });
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
        $requests    = new UserFriendLevel();
        $auth        = auth()->user();
        $arr         = [$auth->user_id, $friendId];
        sort($arr);

        $relation    = UserFriendRelationship::where(['is_delete'=>0,'id'=>$relation_id])->first();
        if (empty($relation)) {
            return $this->response->errorNotFound('该关系不存在');
        }

        list($userId, $friendId)   = $arr;
        $requests->user_id         = $userId;
        $requests->friend_id       = $friendId;
        $requests->relationship_id = $relation_id;
        $requests->save();
        $user = new UserCollection($auth);
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

        if (!blank($userFriend) && !empty($friendUser)) {
            UserFriendLevel::where(['user_id'=>$user_id,'friend_id'=>$friendId, 'is_delete'=>0, 'status'=>0])->update(['status'=>1]);
        }

        $user = new UserCollection($user);
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
