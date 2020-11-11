<?php

namespace App\Http\Controllers\V1;

use App\Jobs\FriendLevel;
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
        $userId   = $user->user_id;
        $requests = new UserFriendRequest();
        $requests->request_from_id = $userId;
        $requests->request_to_id   = $friendId;
        $requests->save();
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
        $flag = true;
        DB::beginTransaction();
        try{
            $friendRequest = DB::table('friends_requests')->where('request_from_id', $friendId)->where('request_to_id', $userId)->where('request_state' , 0)->update(['request_state'=>$state]);
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
                        ($userFriend->relation==0||$friendUser->relation==0)&&$flag=false;
                        $userFriend->relation==0&&DB::table('users_friends')->where('user_id', $userId)->where('friend_id', $friendId)->update(['relation'=>$state]);
                        $friendUser->relation==0&&DB::table('users_friends')->where('user_id', $friendId)->where('friend_id', $userId)->update(['relation'=>$state]);
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

        // 融云推送 聊天
        $flag&&FriendLevel::sendMsgToRyByPerson($userId, $friendId, 'Yooul:FriendRequestReposed', [
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
        $friendRequest = DB::table('friends_requests')->where('request_from_id', $friendId)->where('request_to_id', $userId)->where('request_state' , 0)->update(['request_state'=>$requestState]);
        // 融云推送 聊天
        $friendRequest>0&&FriendLevel::sendMsgToRyByPerson($userId, $friendId, 'Yooul:FriendRequestReposed', [
            'content'  => 'friend response',
            'reposed'  => $requestState,
            'userInfo' => $user
        ], $this->agent);
        return $this->response->accepted();
    }
}
