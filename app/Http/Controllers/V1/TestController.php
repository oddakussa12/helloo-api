<?php

namespace App\Http\Controllers\V1;


use Illuminate\Queue\RedisQueue;
use App\Repositories\Contracts\UserRepository;
use App\Resources\UserCollection;
use Illuminate\Http\Request;
use App\Messages\SignInMessage;
use App\Custom\EasySms\PhoneNumber;
use App\Custom\Uuid\RandomStringGenerator;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;



class TestController extends BaseController
{
    public function index()
    {
        $sms = app('easy-sms');
        $number = new PhoneNumber(7529908826 , 44);
        try{
            $result = $sms->send($number, new SignInMessage(1234) , array('yunXinCustom'));
            \Log::error($result);
        }catch (NoGatewayAvailableException $e)
        {
            $exception = $e->getLastException();
            \Log::error($exception->getMessage());
        }

    }

    public function push()
    {
        $user = app(UserRepository::class)->find(94);
        $content = array(
            'content'=>'test message',
            'userInfo'=>[
                'extra'=> [
                    'un' => !empty($user->user_nick_name) ? $user->user_nick_name : ($user->user_name ?? ''),
                    'ua' => userCover($user->user_avatar ?? ''),
                    'ui' => $user->user_id,
                    'ug' => $user->user_gender,
                    'devicePlatformName' => 'server',
                ]
            ]
        );
        $content = \json_encode($content , JSON_UNESCAPED_UNICODE);
        $result = app('rcloud')->getMessage()->Broadcast()->recall(array(
            'senderId'   => 'system',
//            'targetId'   => $this->targetId,
            "objectName" => "RC:SightMsg",
            'content'    => \json_encode(array(
                'sightUrl'=>1,
                'content'=>1,
                'duration'=>1,
                'size'=>1,
                'name'=>1,
                'user'=>array(
                    'id'=>1
                ),
                'extra'=>'extra'
            ))
,
            'extra'=>array('user'=>1234567890),
        ));
        \Log::error($content);
        \Log::error(\json_encode($result , JSON_UNESCAPED_UNICODE));
    }


    public function token()
    {
        if(domain()!=config('app.url'))
        {
            $token = app('rcloud')->getUser()->register(array(
                'id'=> time(),
                'name'=> (new RandomStringGenerator())->generate(16),
                'portrait'=> "https://qnwebothersia.mmantou.cn/default_avatar.jpg?imageView2/0/w/50/h/50/interlace/1|imageslim"
            ));
            return $this->response->array($token);
        }
    }


}
