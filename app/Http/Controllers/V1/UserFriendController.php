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

class UserFriendController extends BaseController
{
    /**
     * @var UserFriendRepository
     */
    private $userFriend;


    /**
     * UserFriendController constructor.
     * @param UserFriendRepository $userFriendRepository
     */
    public function __construct(UserFriendRepository $userFriend)
    {
        $this->userFriend = $userFriend;
    }


    /**
     * @param int $userId
     * @return mixed
     */
    public function index(int $userId)
    {
        $userFriends = $this->userFriend->paginateByUser($userId);
        $friendIds = $userFriends->pluck('friend_id')->all();
        $friends = app(UserRepository::class)->findByMany($friendIds);
        $userFriends->each(function($friend , $key) use ($friends){
            $friend->friend = $friends->where('user_id' , $friend->friend_id)->first();
        });
        $userFriends = $userFriends->filter(function ($userFriend, $key) {
            return !blank($userFriend->friend);
        });
        return UserFriendCollection::collection($userFriends);
    }

    /**
     * @param int $friendId
     * @return string
     */
    public function store(int $friendId)
    {
        $createdAt = time();
        $userId = auth()->id();
        $sql = <<<DOC
INSERT INTO `f_users_friends` ( `user_id`, `friend_id`, `created_at`) SELECT {$userId}, {$friendId}, {$createdAt} FROM DUAL WHERE NOT EXISTS ( SELECT `id` FROM `f_users_friends` WHERE `user_id` = {$userId} AND `friend_id` = {$friendId} )
DOC;
        DB::insert($sql);
        return $this->response->f_friends_request();
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function my(Request $request)
    {
        $userId = auth()->id();
        $userFriends = $this->userFriend->getAllByUser($userId);
        $friendIds = $userFriends->pluck('friend_id')->all();
        $friends = app(UserRepository::class)->findByMany($friendIds);
        $friends = $friends->each(function($friend , $key) use ($userFriends){
            $friend->make_friend_created_at = $userFriends->where('friend_id' , $friend->user_id)->pluck('created_at')->first();
        });
        return UserCollection::collection($friends);
    }

    public function destroy($friendId)
    {
        $userId = auth()->id();
        $myselfSql = <<<DOC
delete from `f_users_friends` where `user_id`={$userId} and `friend_id`={$friendId};
DOC;
        $friendSql = <<<DOC
delete from `f_users_friends` where `user_id`={$friendId} and `friend_id`={$userId};
DOC;
        DB::statement($myselfSql);
        DB::statement($friendSql);
        app('rcloud')->getMessage()->Person()->send(array(
            'senderId'=> $userId,
            'targetId'=> $friendId,
            "objectName"=>'Yooul:FriendDelete',
            'content'=>['content'=>'friend delete']
        ));
        return $this->response->noContent();
    }
}
