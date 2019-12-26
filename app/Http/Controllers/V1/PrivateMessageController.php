<?php

namespace App\Http\Controllers\V1;

use App\Jobs\Jpush;
use Illuminate\Http\Request;
use App\Services\TranslateService;

class PrivateMessageController extends BaseController
{

    private $translate;


    public function __construct(TranslateService $translate)
    {
        $this->translate = $translate;
    }
    //
    public function translate(Request $request)
    {
        $content = $request->input('content' , array());
        $target = $request->input('target' , 'en');
        if(empty($content))
        {
            return $this->response->array(array('translate'=>$content , 'target'=>$target));
        }
        $languagesFile = 'google/languages.json';
        if(\Storage::exists($languagesFile))
        {
            $languages = \Storage::get($languagesFile);
            $languages = \json_decode($languages , true);
            if(!in_array($target , $languages))
            {
                return $this->response->array(array('translate'=>$content , 'target'=>$target));
            }
        }
        $content = !is_array($content)?[$content]:$content;
//        $originLang = $this->translate->detectLanguageBatch($content);
        $translate = $this->translate->onlyTranslate($content , array('target'=>$target));
        return $this->response->array(array('translate'=>$translate , 'target'=>$target));
    }

    public function push(Request $request)
    {
        $type = $request->input('type');
        $userId = $request->input('user_id');
        $content = $request->input('content');
        switch ($type)
        {
            case 'privatechat':
                Jpush::dispatch('privateMessage' , '' , $userId , $content)->onQueue('op_jpush');
                break;
            default:
                break;
        }
        return $this->response->noContent();
    }
<<<<<<< Updated upstream
=======

>>>>>>> Stashed changes
    public function token()
    {
        if(auth()->check())
        {
            $userId = auth()->id();
            $user = auth()->user();
            $name = $user->user_name;
            $avatar = $user->user_avatar;
            $token = \RongCloud::getToken($userId, $name, $avatar);
            return $this->response->array($token);
        }
        return $this->response->noContent();
    }

<<<<<<< Updated upstream
=======
    public function userCheckOnline($userId)
    {
        try{
            $ret = \RongCloud::userCheckOnline($userId);
        }catch (\Exception $e)
        {
            $ret = array('code'=>500 , 'message'=>$e->getMessage());
        }
        return $this->response->array($ret);
    }
>>>>>>> Stashed changes
    
}
