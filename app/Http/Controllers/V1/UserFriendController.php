<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Resources\UserFriendCollection;
use App\Repositories\Contracts\UserRepository;
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
        return $this->response->accepted();
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function my(Request $request)
    {
        $userId = auth()->id();
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
}
