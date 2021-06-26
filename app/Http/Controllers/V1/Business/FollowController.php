<?php

namespace App\Http\Controllers\V1\Business;

use Illuminate\Http\Request;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Redis;
use App\Resources\AnonymousCollection;
use App\Http\Controllers\V1\BaseController;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Database\Concerns\BuildsQueries;
use function GuzzleHttp\Psr7\str;

class FollowController extends BaseController
{
    use BuildsQueries;

    public function store(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $followedId = $request->input('followed_id' , '');
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

    public function destroy($id)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $followedId = $id;
        $followed = DB::table('users_follows')->where('user_id' , $userId)->where('followed_id' , $followedId)->first();
        if(!empty($followed))
        {
            $now = date('Y-m-d H:i:s');
            $data = $followed->toArray();
            $data['deleted_at'] = $now;
            try{
                DB::beginTransaction();
                DB::table('users_follows')->where('user_id' , $userId)->where('followed_id' , $followedId)->delete();
                DB::table('users_follows')->insert($data);
                DB::commit();
            }catch (\Exception $e)
            {

            }
        }
        return $this->response->noContent();
    }
}
