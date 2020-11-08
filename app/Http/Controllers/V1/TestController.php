<?php

namespace App\Http\Controllers\V1;


use Illuminate\Http\Request;
use Overtrue\EasySms\PhoneNumber;
use App\Custom\Uuid\RandomStringGenerator;



class TestController extends BaseController
{
    public function index()
    {
        $sms = app('easy-sms');
        try{
            $sms->send('17600128988', [
                'content'  => '您的验证码为: 6379',
                'template' => '14876688',
                'data' => [
                    'code' => 6379
                ],
            ]);
        }catch (\Exception $e)
        {
            \Log::error($e->getMessage());
        }

    }


    public function token()
    {
        $token = app('rcloud')->getUser()->register(array(
            'id'=> time(),
            'name'=> (new RandomStringGenerator())->generate(16),
            'portrait'=> "https://qnwebothersia.mmantou.cn/default_avatar.jpg?imageView2/0/w/50/h/50/interlace/1|imageslim"
        ));
        return $this->response->array($token);
    }


}
