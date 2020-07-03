<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use App\Resources\UserCollection;
use App\Models\UserFriendRequest;
use Illuminate\Support\Facades\DB;
use App\Resources\UserFriendCollection;
use App\Repositories\Contracts\UserRepository;
use App\Http\Requests\StoreUserFriendRequestRequest;
use App\Repositories\Contracts\UserFriendRepository;
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
        app('rcloud')->getMessage()->Person()->send(array(
            'senderId'=> $requests->request_from_id,
            'targetId'=> $requests->request_to_id,
            "objectName"=>'Yooul:FriendRequest',
            'content'=>\json_encode([
                'content'=>'friend request',
                'user'=> $user
            ])
        ));
        return $this->response->created();
    }

    public function accept($friendId , Request $request)
    {
        $requestState = 1;
        UserFriendRequest::where('request_from_id' , $friendId)->where('request_to_id' , auth()->id())->update(array('request_state'=>$requestState));
        $createdAt = time();
        $auth = auth()->user();
        $userId = $auth->user_id;
        $mySql = <<<DOC
INSERT INTO `f_users_friends` ( `user_id`, `friend_id`, `created_at`) SELECT {$userId}, {$friendId}, {$createdAt} FROM DUAL WHERE NOT EXISTS ( SELECT `id` FROM `f_users_friends` WHERE `user_id` = {$userId} AND `friend_id` = {$friendId} );
DOC;
        $friendSql = <<<DOC
INSERT INTO `f_users_friends` ( `user_id`, `friend_id`, `created_at`) SELECT {$friendId}, {$userId}, {$createdAt} FROM DUAL WHERE NOT EXISTS ( SELECT `id` FROM `f_users_friends` WHERE `user_id` = {$friendId} AND `friend_id` = {$userId} );
DOC;
        DB::statement($mySql);
        DB::statement($friendSql);
        $user = new UserCollection($auth);
        $user->extra = array(
            'devicePlatformName'=>'Server'
        );
        app('rcloud')->getMessage()->Person()->send(array(
            'senderId'=> $userId,
            'targetId'=> $friendId,
            "objectName"=>'Yooul:FriendRequestReposed',
            'content'=>\json_encode(
                [
                    'content'=>'friend response' ,
                    'reposed'=>$requestState,
                    'user'=> $user
                ]
            )
        ));
        return $this->response->accepted();
    }

    public function refuse($friendId , Request $request)
    {
        $requestState = -1;
        UserFriendRequest::where('request_from_id' , $friendId)->where('request_to_id' , auth()->id())->update(array('request_state'=>$requestState));
        $createdAt = time();
        $auth = auth()->user();
        $userId = $auth->user_id;
        $mySql = <<<DOC
INSERT INTO `f_users_friends` ( `user_id`, `friend_id`, `created_at`) SELECT {$userId}, {$friendId}, {$createdAt} FROM DUAL WHERE NOT EXISTS ( SELECT `id` FROM `f_users_friends` WHERE `user_id` = {$userId} AND `friend_id` = {$friendId} );
DOC;
        $friendSql = <<<DOC
INSERT INTO `f_users_friends` ( `user_id`, `friend_id`, `created_at`) SELECT {$friendId}, {$userId}, {$createdAt} FROM DUAL WHERE NOT EXISTS ( SELECT `id` FROM `f_users_friends` WHERE `user_id` = {$friendId} AND `friend_id` = {$userId} );
DOC;
        DB::statement($mySql);
        DB::statement($friendSql);
        $user = new UserCollection($auth);
        $user->extra = array(
            'devicePlatformName'=>'Server'
        );
        app('rcloud')->getMessage()->Person()->send(array(
            'senderId'=> $userId,
            'targetId'=> $friendId,
            "objectName"=>'Yooul:FriendRequestReposed',
            'content'=>\json_encode(
                [
                    'content'=>'friend response',
                    'reposed'=>$requestState,
                    'user'=> $user
                ]
            )
        ));
        return $this->response->accepted();
    }
}
