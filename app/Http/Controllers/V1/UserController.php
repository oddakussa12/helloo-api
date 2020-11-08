<?php

namespace App\Http\Controllers\V1;

use App\Custom\Constant\Constant;
use App\Models\Es;
use App\Models\User;
use App\Events\Follow;
use App\Events\UnFollow;
use App\Models\UserEmoji;
use App\Models\UserFriend;
use App\Models\UserVisitLog;
use App\Resources\UserSearchCollection;
use App\Traits\CachableUser;
use Illuminate\Http\Request;
use App\Resources\UserCollection;
use App\Resources\FollowCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Contracts\UserRepository;

class UserController extends BaseController
{

    use CachableUser;

    /**
     * @var UserRepository
     */
    private $user;
    /**
     * @var \Illuminate\Config\Repository|\Illuminate\Foundation\Application|mixed
     */
    private $searchUser;

    public function __construct(UserRepository $user)
    {
        $this->user = $user;
        $this->searchUser = config('scout.elasticsearch.user');
    }


    /**
     * Display the specified resource.
     *
     * @param $id
     * @return UserCollection
     */
    public function show($id)
    {
        if ($this->isBlocked($id)) {
            return $this->response->errorNotFound();
        }

        $user        = $this->user->findOrFail($id);
        return new UserCollection($user);
    }


    public function block($userId)
    {
        $this->user->blockUser($userId);
        return $this->response->created();
    }

    public function unblock($userId)
    {
        $this->user->unblockUser($userId);
        return $this->response->created();
    }


    public function randRyOnlineUser(Request $request)
    {
        $userId = $this->user->randDiffRyOnlineUser();
        $userId = $userId>=100?mt_rand(1 , 10):$userId;
        $user = $this->user->findByUserId($userId);
        if(blank($user))
        {
            return $this->response->errorNotFound('Failed to find friends, please try again');
        }
        return new UserCollection($user);
    }

    public function isRyOnline($id)
    {
        return $this->response->array(array(
            'status'=>$this->user->isOnline($id)
        ));
    }

    public function updateRyUserOnlineState(Request $request)
    {
        $response = $this->response->noContent();
        $users = $request->post();
        $this->user->updateUserOnlineState($users);
        return $response->setStatusCode(200);
    }


    public function planet()
    {
        $data = $this->user->planet();
        $data = array_unique($data);
        $userId = intval(auth()->id());
        $data = array_diff($data , [$userId]);
        array_push($data , 1 , 2 , 3 , 4 , 5 , 6 , 7 , 8);//test
        $users = $this->user->findByMany($data);
        $total = $this->user->onlineUsersCount();
        $users = UserCollection::collection($users)->additional(array(
            'total'=>$total
        ));
        return $users;
    }


}
