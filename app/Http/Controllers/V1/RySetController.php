<?php

namespace App\Http\Controllers\V1;

use App\Models\BlackUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class RySetController extends BaseController
{

    public function index(Request $request)
    {

    }

    public function unblock(Request $request)
    {
        $key = 'block_user';
        $userId = intval($request->input('user_id' , 0));
        if($userId<=0)
        {
            return $this->response->errorNotFound();
        }
        $time = Redis::zScore($key, $userId);
        try{
            $res = \RongCloud::getUser()->Block()->remove(array('id'=>$userId));
            Redis::zRem($key, $userId);
            $res['userId'] = $userId;
            $res['message'] = 'ok';
            throw_if($res['code']!=200 , new \Exception('internal error'));
        }catch (\Throwable $e){
            if($time!==null)
            {
                Redis::zadd($key,$time , $userId);
            }
            $res = array(
                'code'=>500,
                'userId'=>$userId,
                'message'=>$e->getMessage(),
            );
            \Log::error(\json_encode($res));
        }
        return $this->response->array($res);
    }

    public function block(Request $request)
    {
        return $this->blockUser($request);
    }

    /**
     * @param Request $request
     * 屏蔽用户
     */
    public function blockUser(Request $request)
    {
        $key      = 'block_user';
        $userId   = intval($request->input('user_id' , 0));
        $operator = intval($request->input('operator' , 0));
        $desc     = strval($request->input('desc' , ''));
        $minute   = intval($request->input('minute' , 43200));
        if($userId<=0) {
            return $this->response->errorNotFound();
        }
        try {
            $start = time();
            $res            = \RongCloud::getUser()->Block()->add(array('id'=>$userId, 'minute'=>$minute));
            $res['userId']  = $userId;
            $res['minute']  = $minute;
            $res['message'] = 'ok';

            Redis::zadd($key, time(), $userId);
            $blackUser = BlackUser::where('user_id' , $userId)->orderBy('updated_at' , "DESC")->first();
            if(blank($blackUser))
            {
                BlackUser::create(
                    array(
                        'user_id'=>$userId,
                        'desc'=>$desc,
                        'start_time'=>$start,
                        'end_time'=>$start+$minute*60,
                        'operator'=>$operator,
                    )
                );
            }else{
                $blackUser->start_time = $start;
                $blackUser->end_time = $start+$minute*60;
                $blackUser->save();
            }
            throw_if($res['code']!=200 , new \Exception('internal error'));
        } catch (\Throwable $e) {
            Redis::zRem($key, $userId);
            $res = array(
                'code'    => $e->getCode(),
                'userId'  => $userId,
                'minute'  => $minute,
                'message' => $e->getMessage(),
            );
            \Log::error(\json_encode($res));
        }
        return $this->response->array($res);

    }


    public function unblockUser(Request $request)
    {
        $key = 'block_user';
        $userId = intval($request->input('user_id' , 0));
        $userName = intval($request->input('user_name' , ''));
        if($userId<=0) {
            return $this->response->errorNotFound();
        }

        $time = Redis::zScore($key, $userId);
        try{
            $res = \RongCloud::getUser()->Block()->remove(array('id'=>$userId));
            Redis::zRem($key, $userId);
            $res['userId']  = $userId;
            $res['message'] = 'ok';

            BlackUser::where('user_id', $userId)->update(['is_delete'=>1]);

            throw_if($res['code']!=200 , new \Exception('internal error'));
        }catch (\Throwable $e){
            if($time!==null) {
                Redis::zadd($key,$time , $userId);
            }
            $res = array(
                'code'=>$e->getCode(),
                'userId'=>$userId,
                'message'=>$e->getMessage(),
            );
            \Log::error(\json_encode($res));
        }
        return $this->response->array($res);
    }

    public function userCheckOnline($userId)
    {
        try{
            $ret = \RongCloud::getUser()->Onlinestatus()->check(array('id'=>$userId));
            throw_if($ret['code']!=200 , new \Exception('internal error'));
        }catch (\Throwable $e)
        {
            $ret = array('code'=>500 , 'message'=>$e->getMessage());
        }
        return $this->response->array($ret);
    }

    public function token()
    {
        $response = $this->response;
        $user = auth()->user();
        $userId = $user->user_id;
        $name = empty($user->user_nick_name)?'guest':$user->user_nick_name;
        $avatar = $user->user_avatar_link;
        $key = 'helloo:account:service:account-ry-token:'.$userId;
        if(Redis::exists($key))
        {
            $token = Redis::get($key);
            if(!empty($token))
            {
                return $response->array(\json_decode($token , true));
            }
        }
        try{
            $token = app('rcloud')->getUser()->register(array(
                'id'=> $userId,
                'name'=> $name,
                'portrait'=> $avatar
            ));
            if(empty($token))
            {
                $token = app('rcloud')->getUser()->register(array(
                    'id'=> $userId,
                    'name'=> $name,
                    'portrait'=> $avatar
                ));
            }
            throw_if($token['code']!=200 , new \Exception($token['code'].'===>'.$token['msg']));
            Redis::set($key , \json_encode($token));
            Redis::expire($key , 60*60*24*15);
        }catch (\Throwable $e)
        {
            $token = array(
                'code'=>500,
                'userId'=>$userId,
                'message'=>$e->getMessage(),
            );
            Log::info('token_fail' , $token);
        }
        return $response->array($token);

    }
}
