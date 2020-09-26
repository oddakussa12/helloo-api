<?php

namespace App\Http\Controllers\V1;

use App\Custom\Constant\Constant;
use App\Jobs\Friend;
use App\Jobs\FriendSignIn;
use Illuminate\Http\Request;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use App\Resources\UserFriendCollection;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\UserFriendRepository;
use Illuminate\Support\Facades\Redis;

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

    /**
     * @param $friendId
     * @return \Dingo\Api\Http\Response
     * 删除用户及其相关
     */
    public function destroy($friendId)
    {
        $userId = auth()->id();
        $user   = auth()->user();

        if (empty($userId) || empty($friendId)) {
            return $this->response->noContent();
        }

        DB::transaction(function() use ($userId, $friendId) {

            // 删除好友关系
            DB::delete("delete from `f_users_friends` where `user_id`={$friendId} and `friend_id`={$userId}");
            DB::delete("delete from `f_users_friends` where `user_id`={$userId}   and `friend_id`={$friendId}");

            // 删除情侣关系相关
            $time = time();

            list($user_id, $friend_id) = FriendSignIn::sortId($userId, $friendId);

            Redis::del(Constant::RY_CHAT_FRIEND_IS_FRIEND.$user_id.'_'.$friend_id);
            Redis::del(Constant::RY_CHAT_FRIEND_SIGN_IN.$user_id.'_'.$friend_id);
            Redis::del(Constant::RY_CHAT_FRIEND_RELATIONSHIP.$user_id.'_'.$friend_id);
            Redis::del(Constant::FRIEND_RELATIONSHIP_MAIN.$user_id.'_'.$friend_id);
            Redis::del(Constant::FRIEND_RELATIONSHIP_HOME_TOP.$friendId);

            $sql = " set is_delete = 1, deleted_at = $time where user_id= $user_id and friend_id = $friend_id ";

            // 关系等级及历史
            DB::update("update f_users_friends_level  $sql");
            DB::update("update f_users_friends_level_history $sql");

            // 签到
            DB::update("update f_users_friends_sign_in $sql");
            // DB::update("update f_users_friends_sign_in_month $sql");

            // 聊天记录条数统计
            DB::update("update f_users_friends_talk $sql");
            DB::update("update f_users_friends_talk_list $sql");

            });
        $this->dispatch((new Friend($userId, $friendId, 'Yooul:FriendRequestReposed', ['content'=>'friend delete', 'userInfo'=>$user]))->onQueue('friend'));
        return $this->response->noContent();
    }
}
