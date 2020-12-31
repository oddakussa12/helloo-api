<?php

namespace App\Http\Controllers\V1;

use App\Jobs\FriendLevel;
use App\Models\UserFriend;
use Carbon\Carbon;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use Godruoyi\Snowflake\Snowflake;
use App\Models\UserFriendRequest;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
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
        app(UserRepository::class)->findOrFail($friendId);
        $userId   = $user->user_id;
        $friend = UserFriend::where('user_id' , $user->user_id)->where('friend_id' , $friendId)->first();
        if(blank($friend))
        {
            $requestModel = UserFriendRequest::where('request_from_to' , $userId."-".$friendId)->first();
            if(blank($requestModel))
            {
                $requests = new UserFriendRequest();
                $request_id = (new Snowflake)->id();
                $requests->request_id = $request_id;
                $requests->request_from_to = $userId.'-'.$friendId;
                $requests->request_from_id = $userId;
                $requests->request_to_id = $friendId;
                $requests->save();
            }else{
                $request_id = $requestModel->request_id;
                $requestModel->request_state=0;
                $requestModel->save();
            }

            $content = array(
                'senderId'   => $userId,
                'targetId'   => $friendId,
                "objectName" => "Helloo:FriendRequest",
                'content'    => array(
                    'content'=>'friend request',
                    'request_id'=>$request_id,
                    'user'=> collect(new UserCollection($user))->toArray()
                ),
                'pushContent'=>'friend request',
                'pushExt'=>\json_encode(array(
                    'title'=>'friend request',
                    'forceShowPushContent'=>1
                ))
            );
            Log::info('request_content' , $content);
            $result = app('rcloud')->getMessage()->System()->send($content);
            Log::info('request_result' , $result);
        }

        return $this->response->created();
    }

    public function accept($requestId)
    {
        $user   = auth()->user();
        $userId = $user->user_id;
        $userRequest = new UserFriendRequest();
        $request = $userRequest->where('request_id' , $requestId)->first();
        if(empty($request)||$request->request_state!=0||$request->request_to_id!=$userId)
        {
            return $this->response->accepted();
        }

        $friendId = $request->request_from_id;
        $state  = 1;
        $flag = true;
        $now = Carbon::now()->timestamp;
        DB::beginTransaction();
        try{
            $friendRequest = DB::table('friends_requests')->where('request_id', $requestId)->update([
                'request_state'=>$state,
                'request_updated_at'=>$now,
            ]);
            if($friendRequest>0)
            {
                $userFriend = DB::table('users_friends')->where('user_id', $userId)->where('friend_id', $friendId)->first();
                if(blank($userFriend))
                {
                    DB::table('users_friends')->insert(array(
                        array('user_id'=>$userId , 'friend_id'=>$friendId , 'created_at'=>$now)
                    ));
                }else{
                    if($userFriend->relation==0)
                    {
                        DB::table('users_friends')->where('id' , $userFriend->id)->update(array(
                            'relation'=>1
                        ));
                    }
                }
                $friendUser = DB::table('users_friends')->where('user_id', $friendId)->where('friend_id', $userId)->first();
                if(blank($friendUser))
                {
                    DB::table('users_friends')->insert(array(
                        array('user_id'=>$friendId , 'friend_id'=>$userId , 'created_at'=>$now)
                    ));
                }else{
                    if($friendUser->relation==0)
                    {
                        DB::table('users_friends')->where('id' , $friendUser->id)->update(array(
                            'relation'=>1
                        ));
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
            $content = array(
                'senderId'   => $userId,
                'targetId'   => $friendId,
                "objectName" => "Helloo:FriendRequestResponse",
                'content'    => array(
                    'response'  => 1,
                    'content'=>'friend response',
                    'request_id'=>$requestId,
                    'user'=> collect(new UserCollection($user))->toArray()
                ),
                'pushContent'=>'friend response',
                'pushExt'=>\json_encode(array(
                    'title'=>'friend response',
                    'forceShowPushContent'=>1
                ))
            );
            Log::info('accept_content' , $content);
            $result = app('rcloud')->getMessage()->System()->send($content);
            Log::info('accept_result' , $result);
            return $this->response->created();
        }
        return $this->response->accepted();
    }

    public function refuse($requestId)
    {
        $user   = auth()->user();
        $userId = $user->user_id;
        $request = UserFriendRequest::where('request_id' , $requestId)->first();
        if(empty($request)||$request->request_state!=0||$request->request_to_id!=$userId)
        {
            return $this->response->accepted();
        }
        $friendId = $request->request_from_id;
        $state  = -1;
        $flag = true;
        $now = Carbon::now()->timestamp;
        DB::beginTransaction();
        try{
            DB::table('friends_requests')->where('request_id', $requestId)->update([
                'request_state'=>$state,
                'request_updated_at'=>$now
            ]);
            DB::commit();
        }catch (\Exception $e)
        {
            DB::rollBack();
            $flag = false;
            Log::error('friend_request_accept_failed:'.\json_encode($e->getMessage() , JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        }
        if($flag)
        {
            $content = array(
                'senderId'   => $userId,
                'targetId'   => $friendId,
                "objectName" => "Helloo:FriendRequestResponse",
                'content'    => array(
                    'response'  => $state,
                    'content'=>'friend response',
                    'request_id'=>$requestId,
                    'user'=> collect(new UserCollection($user))->toArray()
                ),
                'pushContent'=>'friend response',
                'pushExt'=>\json_encode(array(
                    'title'=>'friend response',
                    'forceShowPushContent'=>1
                ))
            );
            Log::info('refuse_content' , $content);
            $result = app('rcloud')->getMessage()->System()->send($content);
            Log::info('refuse_result' , $result);
            return $this->response->created();
        }
        return $this->response->accepted();
    }
}
