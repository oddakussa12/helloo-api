<?php

namespace App\Http\Controllers\V1;


use App\Traits\CachableUser;
use Illuminate\Http\Request;
use App\Resources\TagCollection;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use App\Repositories\Contracts\TagRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Contracts\UserTagRepository;

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

    public function __construct(UserRepository $user)
    {
        $this->user = $user;
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
        $user = $this->user->findOrFail($id);
        $like = DB::table('likes')->where('user_id' , auth()->id())->where('like_id' , $id)->first();
        $user->likeState = !blank($like);
        $user->likedCount = 0;
        $user->friendCount = 0;
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

    public function randomVideo(Request $request)
    {
        $self = auth()->id();
        $random = $this->user->randomVideo($self);
        if($random['flag']==true)
        {
            $random['user'] = new UserCollection($this->user->findByUserId($random['userId']));
        }
        unset($random['userId']);
        return $this->response->array($random);
    }

    public function randomVoice(Request $request)
    {
        $self = auth()->id();
        $random = $this->user->randomVoice($self);
        if($random['flag']==true)
        {
            $random['user'] = new UserCollection($this->user->findByUserId($random['userId']));
        }
        unset($random['userId']);
        return $this->response->array($random);
    }

    public function removeVideo(Request $request)
    {
        $this->user->removeVideo();
        return $this->response->noContent();
    }

    public function removeVoice(Request $request)
    {
        $this->user->removeVoice();
        return $this->response->noContent();
    }


    public function randRyOnlineUser(Request $request)
    {
        $self = auth()->id();
        $userId = $this->user->randomIm($self);
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
        $data = range(61 , 108);
        $data = array_diff($data , [$userId]);
//        array_push($data , 1 , 2 , 3 , 4 , 5 , 6 , 7 , 8);//test
        $users = $this->user->findByMany($data);
        $total = $this->user->onlineUsersCount();
        $users = UserCollection::collection($users)->additional(array(
            'total'=>$total
        ));
        return $users;
    }

    public function tag($userId)
    {
        $userTags = app(UserTagRepository::class)->getByUserId($userId);
        $tagIds = $userTags->pluck('tag_id')->all();
        $tags = app(TagRepository::class)->findByMany($tagIds);
        return TagCollection::collection($tags);
    }

    public function like($userId)
    {
        $authId = auth()->id();
        $like = DB::table('likes')->where('user_id' , $authId)->where('like_id' , $userId)->first();
        if(empty($like))
        {
            $liked = DB::table('users')->where('user_id' , $userId)->first();
            if(empty($liked))
            {
                DB::table('likes')->insert(
                    array('user_id'=>$authId , 'like_id'=>$userId)
                );
            }
        }
        return $this->response->created();
    }
}
