<?php

namespace App\Http\Controllers\V1;

use App\Models\UserFriend;
use Illuminate\Http\Request;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use App\Jobs\FriendSynchronization;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Jobs\MoreTimeUserScoreUpdate;
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
     * @note 用户好友统计
     * @datetime 2021-07-12 19:08
     * @param $userId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index($userId)
    {
        $privacy = app(UserRepository::class)->findPrivacyByUserId($userId);
        if ($privacy['friend']=='3') {
            return $this->response->array([]);
        }
        if ($privacy['friend'] =='2') {
            $friends = UserFriend::where('user_id' , auth()->id())->where('friend_id', $userId)->first();
            if (empty($friends)) {
                return $this->response->array([]);
            }
        }
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
     * @note 我的好友
     * @datetime 2021-07-12 19:08
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function my(Request $request)
    {
        $userId = auth()->id();
        $userFriends = $this->userFriend->paginateByUser($userId);
        $friendIds = $userFriends->pluck('friend_id')->all();
        $friends = app(UserRepository::class)->findByMany($friendIds);
        $userFriends->each(function($userFriend , $key) use ($friends){
            $userFriend->friend = new UserCollection($friends->where('user_id', $userFriend->friend_id)->first());
        });
        return UserFriendCollection::collection($userFriends);
    }

    /**
     * @note 修改好友信息(备注)
     * @datetime 2021-07-12 19:08
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function update(Request $request)
    {
        $userId   = auth()->id();
        $friendId = $request->input('friend_id');
        $nickName = $request->input('nick_name');

        $this->validate($request, [
            'friend_id' => 'required|int',
            'nick_name' => 'required|string|min:1',
        ]);

        UserFriend::where(['user_id'=>$userId, 'friend_id'=>$friendId])->update(['friend_nick_name'=>$nickName]);
        return $this->response->accepted();
    }

    /**
     * @note 好友删除
     * @datetime 2021-07-12 19:09
     * @param $friendId
     * @return \Dingo\Api\Http\Response
     */
    public function destroy($friendId)
    {
        $userId = auth()->id();
        $user   = auth()->user();

        if (empty($userId) || empty($friendId)) {
            return $this->response->noContent();
        }
        $flag = false;
        DB::beginTransaction();
        try {
            // 删除好友关系
            $userResult = DB::delete("delete from `t_users_friends` where `user_id`={$userId}   and `friend_id`={$friendId}");
            $friendResult = DB::delete("delete from `t_users_friends` where `user_id`={$friendId} and `friend_id`={$userId}");
            if($userResult>0&&$friendResult>0)
            {
                $flag = true;
                DB::commit();
                $mKey = 'helloo:account:user-friends';
                Redis::del($mKey.$userId);
                Redis::del($mKey.$friendId);
            }else{
                throw new \Exception('userId:'.$userId.' and friendId:'.$friendId.' delete fail');
            }
        }catch (\Exception $e)
        {
            DB::rollBack();
            Log::info('friend_delete_failed' , array(
                'code'=>$e->getCode(),
                'message'=>$e->getMessage(),
            ));
        }

        // 融云推送 聊天
        if($flag)
        {
            FriendSynchronization::dispatch($userId , $friendId)->onQueue('helloo_{friend_synchronization}');
            MoreTimeUserScoreUpdate::dispatch($userId , 'friendDestroy' , $friendId)->onQueue('helloo_{more_time_user_score_update}');
            MoreTimeUserScoreUpdate::dispatch($friendId , 'friendDestroyed' , $userId)->onQueue('helloo_{more_time_user_score_update}');
            $sortKey = "helloo:account:friend:game:rank:sort:".$userId.'-coronation';//暂时一个游戏
            $friendSortKey = "helloo:account:friend:game:rank:sort:".$friendId.'-coronation';//暂时一个游戏
            Redis::zrem($sortKey , $friendId);
            Redis::zrem($friendSortKey , $userId);
            $user = collect(new UserCollection($user))->toArray();
            $content = array(
                'senderId'   => $userId,
                'targetId'   => $friendId,
                "objectName" => "Helloo:FriendDelete",
                'content'    => array(
                    'content'=>'friend delete',
                    'user'=> $user
                ),
                'pushContent'=>'friend delete',
                'pushExt'=>\json_encode(array(
                    'title'=>'friend delete',
                    'forceShowPushContent'=>1
                ))
            );
            Log::info('delete_content' , $content);
            $result = app('rcloud')->getMessage()->System()->send($content);
            Log::info('delete_result' , $result);
        }
        return $this->response->noContent();
    }

    /**
     * @note 好友游戏排行
     * @datetime 2021-07-12 19:09
     * @param $game
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function gameRank($game)
    {
        $userId = auth()->id();
        $users = $this->userFriend->getFriendRankByUserId($userId , $game);
        return UserCollection::collection($users);
    }
}
