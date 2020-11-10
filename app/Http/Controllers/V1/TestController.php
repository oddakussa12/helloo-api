<?php

namespace App\Http\Controllers\V1;


use Illuminate\Http\Request;
use App\Messages\SignInMessage;
use Overtrue\EasySms\PhoneNumber;
use App\Custom\Uuid\RandomStringGenerator;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;



class TestController extends BaseController
{
    public function index()
    {
        $sms = app('easy-sms');
        $number = new PhoneNumber(17600128988);
        try{
            $result = $sms->send($number, new SignInMessage(1234) , array('aliYunCustom'));
            \Log::error($result);
        }catch (NoGatewayAvailableException $e)
        {
            $exception = $e->getLastException();
            \Log::error($exception->getMessage());
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
