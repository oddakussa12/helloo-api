<?php

namespace App\Http\Controllers\V1;


use Illuminate\Http\Request;
use App\Custom\Google\GoogleTokenGenerator;


class GoogleController extends BaseController
{

    public function token(Request $request)
    {
        $text = (string)$request->input('text' , '');
        $tkk = (string)$request->input('tkk' , '');
        $google = new GoogleTokenGenerator();
        $token = $google->generateToken($text , $tkk);
        return $this->response->array(array('token'=>$token));
    }


}
