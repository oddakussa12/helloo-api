<?php

namespace App\Http\Controllers\V1;

use App\Jobs\Friend;
use App\Models\UserFriend;
use Illuminate\Http\Request;
use App\Resources\UserCollection;
use App\Models\UserFriendRequest;
use App\Resources\UserFriendCollection;
use App\Repositories\Contracts\UserRepository;
use App\Http\Requests\StoreUserFriendRequestRequest;
use App\Repositories\Contracts\UserFriendRequestRepository;

class UserFriendRequestController extends BaseController
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

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $userFriendRequests = $this->userFriendRequest->paginateByUser(auth()->id());
        $friendIds = $userFriendRequests->pluck('request_from_id')->all();
        $friends = app(UserRepository::class)->findByMany($friendIds);
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
        $requests = new UserFriendRequest();
        $auth = auth()->user();
        $requests->request_from_id = $auth->user_id;
        $requests->request_to_id = $friendId;
        $requests->save();
        $user = new UserCollection($auth);
        $user->extra = array(
            'devicePlatformName'=>'Server'
        );
        $this->dispatch((new Friend($requests->request_from_id , $requests->request_to_id , 'Yooul:FriendRequest' , [
            'content'=>'friend request',
            'user'=> $user
        ]))->onQueue('friend'));
        return $this->response->created();
    }

    public function accept($friendId , Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $requestState = 1;
        UserFriendRequest::where('request_from_id' , $friendId)->where('request_to_id' , $userId)->update(array('request_state'=>$requestState));
        $createdAt = time();
        $userFriend = UserFriend::where('user_id' , $userId)->where('friend_id' , $friendId)->first();
        $friendUser = UserFriend::where('user_id' , $friendId)->where('friend_id' , $userId)->first();
        $friends = array();
        blank($userFriend)&&array_push($friends , array('user_id'=>$userId,'friend_id'=>$friendId,'created_at'=>$createdAt));
        blank($friendUser)&&array_push($friends , array('user_id'=>$friendId,'friend_id'=>$userId,'created_at'=>$createdAt));
        if(!blank($friends))
        {
            UserFriend::insert($friends);
        }
        $user = new UserCollection($user);
        $user->extra = array(
            'devicePlatformName'=>'Server'
        );
        $this->dispatch((new Friend($userId, $friendId , 'Yooul:FriendRequestReposed' , [
            'content'=>'friend response' ,
            'reposed'=>$requestState,
            'user'=> $user
        ]))->onQueue('friend'));
        return $this->response->accepted();
    }

    public function refuse($friendId , Request $request)
    {
        $requestState = -1;
        UserFriendRequest::where('request_from_id' , $friendId)->where('request_to_id' , auth()->id())->update(array('request_state'=>$requestState));
        $auth = auth()->user();
        $userId = $auth->user_id;
        $user = new UserCollection($auth);
        $user->extra = array(
            'devicePlatformName'=>'Server'
        );
        $this->dispatch((new Friend($userId, $friendId , 'Yooul:FriendRequestReposed' , [
            'content'=>'friend response',
            'reposed'=>$requestState,
            'user'=> $user
        ]))->onQueue('friend'));
        return $this->response->accepted();
    }
}
