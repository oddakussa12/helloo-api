<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use libphonenumber\PhoneNumberUtil;
use Illuminate\Support\Facades\Redis;
use libphonenumber\NumberParseException;

class RySetController extends BaseController
{
    /**
     * @note 融云token
     * @datetime 2021-07-12 19:01
     * @return mixed
     */
    public function token()
    {
        $response = $this->response;
        $user = auth()->user();
        $userId = $user->user_id;
        $activation = $user->user_activation;
        if($activation!=1)
        {
            return $response->array(
                array(
                    'code'=>500,
                    'userId'=>$userId,
                    'message'=>'Sorry, please activate this account first!',
                ));
        }
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
            Redis::expire($key , 60*60*24);
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

    /**
     * @note 融云推送
     * @datetime 2021-07-12 19:02
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function push(Request $request)
    {
        Log::info('all' , $request->all());
        $sender = strval($request->input('sender' , ''));
        $target = strval($request->input('target' , ''));
        $type = intval($request->input('type' , 0));
        $image = strval($request->input('image' , ''));
        $video = strval($request->input('video' , ''));
        if(blank($sender))
        {
            return $this->response->created();
        }
        if($type==2&&blank($target))
        {
            return $this->response->created();
        }
        $sendPhone = "+".$sender;
        $phoneUtil = PhoneNumberUtil::getInstance();
        try{
            $numberProto = $phoneUtil->parse($sendPhone);
            $result = $phoneUtil->isValidNumber($numberProto);
            if(!$result)
            {
                return $this->response->created();
            }
            $senderPhone = $numberProto->getNationalNumber();
            $senderPhoneCountry = $numberProto->getCountryCode();
        }catch (NumberParseException $e)
        {
            return $this->response->created();
        }
        if($type==2)
        {
            $targetPhone = "+".$target;
            try{
                $numberProto = $phoneUtil->parse($targetPhone);
                $result = $phoneUtil->isValidNumber($numberProto);
                if(!$result)
                {
                    return $this->response->created();
                }
                $targetErPhone = $numberProto->getNationalNumber();
                $targetErPhoneCountry = $numberProto->getCountryCode();
            }catch (NumberParseException $e)
            {
                return $this->response->created();
            }
            $targetUserPhone = DB::table('users_phones')->where('user_phone_country' , $targetErPhoneCountry)->where('user_phone' , $targetErPhone)->first();
            if(blank($targetUserPhone))
            {
                return $this->response->created();
            }
            $target = $targetUserPhone->user_id;
        }

        $senderUserPhone = DB::table('users_phones')->where('user_phone_country' , $senderPhoneCountry)->where('user_phone' , $senderPhone)->first();
        if(blank($senderUserPhone))
        {
            return $this->response->created();
        }
        $sender = $senderUserPhone->user_id;
        DB::table('push_logs')->insert(array(
            'sender'=>$sender,
            'target'=>$target,
            'type'=>$type,
            'image'=>$image,
            'video'=>$video,
            'created_at'=>Carbon::now()->toDateTimeString(),
        ));
        Redis::set('helloo:message:service:switch' , 1);
        Redis::expire('helloo:message:service:switch' , 60*60*24);
        return $this->response->created();
    }
}
