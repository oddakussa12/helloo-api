<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Repositories\Contracts\UserRepository;

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
            throw_if($res['code']!=200 , $res['msg']);
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
        $key = 'block_user';
        $userId = intval($request->input('user_id' , 0));
        $minute = intval($request->input('minute' , 43200));
        if($userId<=0)
        {
            return $this->response->errorNotFound();
        }
        try{
            $res = \RongCloud::getUser()->Block()->add(array('id'=>$userId , 'minute'=>43200));
            Redis::zadd($key,time() , $userId);
            $res['userId'] = $userId;
            $res['minute'] = $minute;
            $res['message'] = 'ok';
            throw_if($res['code']!=200 , $res['msg']);
        }catch (\Throwable $e)
        {
            Redis::zRem($key, $userId);
            $res = array(
                'code'=>500,
                'userId'=>$userId,
                'minute'=>$minute,
                'message'=>$e->getMessage(),
            );
            \Log::error(\json_encode($res));
        }
        return $this->response->array($res);
    }

    public function blockUser(Request $request)
    {
        $key = 'block_user';
        $userId = intval($request->input('user_id' , 0));
        $userName = intval($request->input('user_name' , ''));
        $minute = intval($request->input('minute' , 43200));
        if($userId<=0)
        {
            return $this->response->errorNotFound();
        }
        if(empty($userName))
        {
            $user = app(UserRepository::class)->findOrFail($userId);
            $userName = $user->user_name;
        }
        try{
            $res = \RongCloud::getUser()->Block()->add(array('id'=>$userId , 'minute'=>$minute));
            Redis::zadd($key,time() , $userId);
            block_user($userName);
            $res['userId'] = $userId;
            $res['minute'] = $minute;
            $res['message'] = 'ok';
            throw_if($res['code']!=200 , $res['msg']);
        }catch (\Throwable $e)
        {
            Redis::zRem($key, $userId);
            unblock_user($userName);
            $res = array(
                'code'=>$e->getCode(),
                'userId'=>$userId,
                'minute'=>$minute,
                'message'=>$e->getMessage(),
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
        if($userId<=0)
        {
            return $this->response->errorNotFound();
        }
        if(empty($userName))
        {
            $user = app(UserRepository::class)->findOrFail($userId);
            $userName = $user->user_name;
        }
        $time = Redis::zScore($key, $userId);
        try{
            $res = \RongCloud::getUser()->Block()->remove(array('id'=>$userId));
            Redis::zRem($key, $userId);
            unblock_user($userName);
            $res['userId'] = $userId;
            $res['message'] = 'ok';
            throw_if($res['code']!=200 , $res['msg']);
        }catch (\Throwable $e){
            if($time!==null)
            {
                Redis::zadd($key,$time , $userId);
                block_user($userName);
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
            throw_if($ret['code']!=200 , $ret['msg']);
        }catch (\Throwable $e)
        {
            $ret = array('code'=>500 , 'message'=>$e->getMessage());
        }
        return $this->response->array($ret);
    }
}
