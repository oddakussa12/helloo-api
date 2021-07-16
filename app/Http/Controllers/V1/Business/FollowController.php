<?php

namespace App\Http\Controllers\V1\Business;


use Illuminate\Http\Request;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\V1\BaseController;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Database\Concerns\BuildsQueries;

class FollowController extends BaseController
{
    use BuildsQueries;

    public function index(Request $request)
    {
        $userId = $request->input('user_id' , 0);
        $perPage  = 10;
        $pageName = 'page';
        $page     = intval($request->input($pageName, 1));
        $follows = DB::table('users_follows')->where('user_id' , $userId)->paginate($perPage , ['*'] , $pageName , $page);
        $followedIds = $follows->pluck('followed_id')->toArray();
        $users = app(UserRepository::class)->findByUserIds($followedIds);
        $users = $this->paginator($users , $follows->total(), $perPage, $page, [
            'path'     => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
        return UserCollection::collection($users);
    }
    /**
     * @note 我的关注
     * @datetime 2021-07-12 17:49
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function my(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $perPage  = 10;
        $pageName = 'page';
        $page     = intval($request->input($pageName, 1));
        $follows = DB::table('users_follows')->where('user_id' , $userId)->paginate($perPage , ['*'] , $pageName , $page);
        $followedIds = $follows->pluck('followed_id')->toArray();
        $users = app(UserRepository::class)->findByUserIds($followedIds);
        $users = $this->paginator($users , $follows->total(), $perPage, $page, [
            'path'     => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
        return UserCollection::collection($users);
    }

    /**
     * @note 关注
     * @datetime 2021-07-12 17:50
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $followedId = $request->input('followed_id' , '');
        $followedUser = app(UserRepository::class)->findOrFail($followedId);
        if($user->user_shop==0&&$followedUser->user_shop==0)
        {
            abort(403 , 'You cannot use this feature!');
        }
        $followed = DB::table('users_follows')->where('user_id' , $userId)->where('followed_id' , $followedId)->first();
        if(empty($followed))
        {
            $now = date('Y-m-d H:i:s');
            $flag = $userId>$followedId?strval($userId).'-'.strval($followedId):strval($followedId).'-'.strval($userId);
            DB::table('users_follows')->insert(array(
                'id'=>app('snowflake')->id(),
                'user_id'=>$userId,
                'followed_id'=>$followedId,
                'flag'=>$flag,
                'created_at'=>$now,
            ));
        }
        return $this->response->created();
    }

    /**
     * @note 取消关注
     * @datetime 2021-07-12 17:50
     * @param $id
     * @return \Dingo\Api\Http\Response
     */
    public function destroy($id)
    {
        $user = auth()->user();
        $authId = $user->user_id;
        $follow = DB::table('users_follows')->where('user_id' , $authId)->where('followed_id' , $id)->first();
        if(empty($follow))
        {
            abort(422 , 'Unfollow failed, please try again!');
        }
        $data = collect($follow)->toArray();
        try {
            DB::beginTransaction();
            $followResult = DB::table('users_follows')->where('id' , $follow->id)->delete();
            if($followResult<=0)
            {
                abort(500 , 'follow delete failed!');
            }
            $data['deleted_at'] = date('Y-m-d H:i:s');
            $followLogResult = DB::table('users_follows_logs')->insert($data);
            if(!$followLogResult)
            {
                abort(500 , 'follow log insert failed!');
            }
            DB::commit();
        }catch (\Exception $e)
        {
            DB::rollBack();
            Log::info('unfollow_fail' , array(
                'message'=>$e->getMessage(),
                'user_id'=>$authId,
                'id'=>$id,
            ));
        }
        return $this->response->noContent();
    }
}
