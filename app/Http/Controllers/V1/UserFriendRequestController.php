<?php

namespace App\Http\Controllers\V1;

use App\Jobs\FriendLevel;
use Illuminate\Support\Facades\Redis;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use App\Models\UserFriendRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        if($friendId==$user->user_id)
        {
            return $this->response->created();
        }
        $friend = app(UserRepository::class)->findOrFail($friendId);
        $userId   = $user->user_id;
        $requests = new UserFriendRequest();
        $requests->request_from_id = $userId;
        $requests->request_to_id   = $friend->user_id;
        $requests->save();
        // 融云推送 聊天
        FriendLevel::sendMsgToRyBySystem($requests->request_from_id, $requests->request_to_id, 'Yooul:FriendRequest', [
            'content'  => 'friend request',
            'userInfo' => $user,
            'request_id'=>$requests->request_id
        ], $this->agent);
    }

    public function accept($requestId)
    {
        $user   = auth()->user();
        $userId = $user->user_id;
        $friendRequest = UserFriendRequest::where('request_id' , $requestId)->first();
        if(empty($friendRequest)||$friendRequest->request_state!=0||$friendRequest->request_from_id!=$userId)
        {
            return $this->response->accepted();
        }
        $friendId = $friendRequest->request_to_id;
        $state  = 1;
        $flag = true;
        DB::beginTransaction();
        try{
            $friendRequest = DB::table('friends_requests')->where('request_id', $requestId)->update(['request_state'=>$state]);
            if($friendRequest>0)
            {
                $userFriend = DB::table('users_friends')->where('user_id', $userId)->where('friend_id', $friendId)->first();
                $friendUser = DB::table('users_friends')->where('user_id', $friendId)->where('friend_id', $userId)->first();
                if(blank($userFriend)&&blank($friendUser))
                {
                    DB::table('users_friends')->insert(array(
                        array('user_id'=>$userId , 'friend_id'=>$friendId),
                        array('user_id'=>$friendId , 'friend_id'=>$userId)
                    ));
                }else{
                    if(blank($userFriend))
                    {
                        DB::table('users_friends')->insert(array(
                            array('user_id'=>$userId , 'friend_id'=>$friendId)
                        ));
                    }elseif(blank($friendUser))
                    {
                        DB::table('users_friends')->insert(array(
                            array('user_id'=>$friendId , 'friend_id'=>$userId)
                        ));
                    }else{
                        $flag=false;
                    }
                }
            }else{
                $flag = false;
            }
            DB::commit();
        }catch (\Exception $e)
        {
            DB::rollBack();
            $flag = false;
            Log::error('friend_request_accept_failed:'.\json_encode($e->getMessage() , JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        }
        if($flag)
        {
            $likedKey = 'helloo:account:service:account-friend-num';
            $userFriendCount = intval(DB::table('users_friends')->where('user_id', $userId)->count());
            $friendFriendCount = intval(DB::table('users_friends')->where('user_id', $friendId)->count());
            Redis::zadd($likedKey , $userFriendCount , $userId);
            Redis::zadd($likedKey , $friendFriendCount , $userId);
            // 融云推送 聊天
            FriendLevel::sendMsgToRyByPerson($userId, $friendId, 'Helloo:FriendRequestReposed', [
                'content'  => 'friend response',
                'reposed'  => $state,
                'userInfo' => $user
            ], $this->agent);
        }
        return $this->response->accepted();
    }

    public function refuse($requestId)
    {
        $requestState = -1;
        $user   = auth()->user();
        $userId = $user->user_id;
        $friendRequest = UserFriendRequest::where('request_id' , $requestId)->first();
        if(empty($friendRequest)||$friendRequest->request_state!=0||$friendRequest->request_from_id!=$userId)
        {
            return $this->response->accepted();
        }
        $friendId = $friendRequest->request_to_id;
        $friendRequest->request_state = $requestState;
        $result = $friendRequest->save();
        // 融云推送 聊天
        $result&&FriendLevel::sendMsgToRyByPerson($userId, $friendId, 'Helloo:FriendRequestReposed', [
            'content'  => 'friend response',
            'reposed'  => $requestState,
            'userInfo' => $user
        ], $this->agent);
        return $this->response->accepted();
    }
}
