<?php

namespace App\Http\Controllers\V1;

use App\Custom\Constant\Constant;
use App\Jobs\Friend;
use App\Jobs\FriendLevel;
use App\Models\UserFriend;
use Illuminate\Http\Request;
use App\Resources\UserCollection;
use App\Models\UserFriendRequest;
use App\Resources\UserFriendCollection;
use App\Repositories\Contracts\UserRepository;
use App\Http\Requests\StoreUserFriendRequestRequest;
use App\Repositories\Contracts\UserFriendRequestRepository;
use Illuminate\Support\Facades\Redis;
use Jenssegers\Agent\Agent;

class UserFriendRequestController extends BaseController
{

    /**
     * @var UserFriendRequestRepository
     */
    private $userFriendRequest;
    private $agent;


    /**
     * UserFriendController constructor.
     * @param UserFriendRequestRepository $userFriendRequest
     */
    public function __construct(UserFriendRequestRepository $userFriendRequest)
    {
        $this->userFriendRequest = $userFriendRequest;
        $this->agent = userAgent(new Agent(), false);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $userFriendRequests = $this->userFriendRequest->paginateByUser(auth()->id());
        $friendIds = $userFriendRequests->pluck('request_from_id')->all();
        $friends   = app(UserRepository::class)->findByMany($friendIds);
        $userFriendRequests->each(function($friend , $key) use ($friends){
            $friend->friend = $friends->where('user_id' , $friend->friend_id)->first();
        });
        $userFriendRequests = $userFriendRequests->filter(function ($userFriendRequest, $key) {
            return !blank($userFriendRequest->friend);
        });
        return UserFriendCollection::collection($userFriendRequests);
    }

    /**
     * @param StoreUserFriendRequestRequest $request
     * @return mixed
     */
    public function store(StoreUserFriendRequestRequest $request)
    {
        $friendId = intval($request->input('friend_id'));
        $user     = auth()->user();
        $userId   = $user->user_id;
        $requests = new UserFriendRequest();
        $requests->request_from_id = $userId;
        $requests->request_to_id   = $friendId;
        $requests->save();

        list($user_id, $friend_id) = FriendLevel::sortId($userId, $friendId);
        Redis::del(Constant::RY_CHAT_FRIEND_IS_FRIEND. $user_id."_".$friend_id);

        // 融云推送 聊天
        FriendLevel::sendMsgToRyByPerson($requests->request_from_id, $requests->request_to_id, 'Yooul:FriendRequest', [
            'content'  => 'friend request',
            'userInfo' => $user
        ], $this->agent);

        return $this->response->created();
    }

    public function accept($friendId)
    {
        $user   = auth()->user();
        $userId = $user->user_id;
        $state  = 1;

        UserFriendRequest::where('request_from_id', $friendId)->where('request_to_id', $userId)->update(['request_state'=>$state]);

        $userFriend = UserFriend::where('user_id', $userId)->where('friend_id', $friendId)->first();
        $friendUser = UserFriend::where('user_id', $friendId)->where('friend_id', $userId)->first();

        $friends    = [];
        $createdAt  = time();
        blank($userFriend) && array_push($friends, ['user_id'=>$userId,'friend_id'=>$friendId,'created_at'=>$createdAt]);
        blank($friendUser) && array_push($friends, ['user_id'=>$friendId,'friend_id'=>$userId,'created_at'=>$createdAt]);

        if(!blank($friends)) {
            UserFriend::insert($friends);
        }

        list($user_id, $friend_id) = FriendLevel::sortId($userId, $friendId);
        Redis::del(Constant::RY_CHAT_FRIEND_IS_FRIEND. $user_id."_".$friend_id);

        // 融云推送 聊天
        FriendLevel::sendMsgToRyByPerson($userId, $friendId, 'Yooul:FriendRequestReposed', [
            'content'  => 'friend response',
            'reposed'  => $state,
            'userInfo' => $user
        ], $this->agent);
        return $this->response->accepted();
    }

    public function refuse($friendId)
    {
        $requestState = -1;
        $user         = auth()->user();
        $userId       = $user->user_id;
        UserFriendRequest::where('request_from_id', $friendId)->where('request_to_id', $userId)->update(['request_state'=>$requestState]);

        // 融云推送 聊天
        FriendLevel::sendMsgToRyByPerson($userId, $friendId, 'Yooul:FriendRequestReposed', [
            'content'  => 'friend response',
            'reposed'  => $requestState,
            'userInfo' => $user
        ], $this->agent);
        return $this->response->accepted();
    }
}
