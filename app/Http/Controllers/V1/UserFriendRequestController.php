<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use App\Jobs\AutoFriend;
use App\Models\UserFriend;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use App\Models\UserFriendRequest;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\FriendSynchronization;
use Illuminate\Support\Facades\Redis;
use App\Jobs\MoreTimeUserScoreUpdate;
use App\Jobs\FriendFromDifferentSchool;
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
        $referrer = strval($request->input('referrer' , ''));
        $user     = auth()->user();
        $userId   = $user->user_id;
        if($friendId==$userId)
        {
            return $this->response->created();
        }
        $friend = app(UserRepository::class)->findOrFail($friendId);
        $userFriend = UserFriend::where('user_id' , $userId)->where('friend_id' , $friendId)->first();
        if(blank($userFriend))
        {
            $requestModel = UserFriendRequest::where('request_from_to' , $userId."-".$friendId)->first();
            if(blank($requestModel))
            {
                $requests = new UserFriendRequest();
                $request_id = app('snowflake')->id();
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
            $user = collect(new UserCollection($user))->toArray();
            $content = array(
                'senderId'   => $userId,
                'targetId'   => $friendId,
                "objectName" => "Helloo:FriendRequest",
                'content'    => array(
                    'content'=>'friend request',
                    'request_id'=>$request_id,
                    'user'=> $user
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
            $kol = DB::table('kol_users')->where('user_id' , $friendId)->first();
            !empty($kol)&&AutoFriend::dispatch($friend , $request_id)->onQueue('helloo_{auto_friend}')->delay(now()->addSeconds(mt_rand(5 , 15)));
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
            $mKey = 'helloo:account:user-friends';
            Redis::del($mKey.$userId);
            Redis::del($mKey.$friendId);
        }catch (\Exception $e)
        {
            DB::rollBack();
            $flag = false;
            Log::info('friend_request_accept_failed' , array(
                'requestId'=>$requestId,
                'code'=>$e->getCode(),
                'message'=>$e->getMessage(),
            ));
        }
        if($flag)
        {
            FriendSynchronization::dispatch($userId , $friendId)->onQueue('helloo_{friend_synchronization}');
            FriendFromDifferentSchool::dispatch($user , $friendId)->onQueue('helloo_{friend_from_different_school}');
            MoreTimeUserScoreUpdate::dispatch($user->user_id , 'friendAccept' , $friendId)->onQueue('helloo_{more_time_user_score_update}');
            MoreTimeUserScoreUpdate::dispatch($friendId , 'friendAccepted' , $user->user_id)->onQueue('helloo_{more_time_user_score_update}');
            $phone = DB::table('users_phones')->where('user_id' , $userId)->first();
            $country = $phone->user_phone_country;
            if(!in_array($country , array(670 , '670' , '62' , 62 , '251' , 251)))
            {
                $country = 'other';
            }
            $key = "helloo:account:game:country:score:coronation".'-'.$country;
            $sortKey = "helloo:account:friend:game:rank:sort:".$userId.'-coronation';//暂时一个游戏
            $friendSortKey = "helloo:account:friend:game:rank:sort:".$friendId.'-coronation';//暂时一个游戏
            $maxScore = Redis::zscore($key , $userId);
            $friendMaxScore = Redis::zscore($key , $friendId);
            if($maxScore!==null)
            {
                Redis::exists($friendSortKey)&&Redis::zadd($friendSortKey , $maxScore , $userId);
            }
            if($friendMaxScore!==null)
            {
                Redis::exists($sortKey)&&Redis::zadd($sortKey , $friendMaxScore , $friendId);
            }
            $user = collect(new UserCollection($user))->toArray();
            $content = array(
                'senderId'   => $userId,
                'targetId'   => $friendId,
                "objectName" => "Helloo:FriendRequestResponse",
                'content'    => array(
                    'response'  => 1,
                    'content'=>'friend response',
                    'request_id'=>$requestId,
                    'user'=> $user
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
            $user = collect(new UserCollection($user))->toArray();
            $content = array(
                'senderId'   => $userId,
                'targetId'   => $friendId,
                "objectName" => "Helloo:FriendRequestResponse",
                'content'    => array(
                    'response'  => $state,
                    'content'=>'friend response',
                    'request_id'=>$requestId,
                    'user'=> $user
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
