<?php

namespace App\Http\Controllers\V1;

use App\Jobs\Jpush;
use Illuminate\Http\Request;
use App\Services\TranslateService;
use Dingo\Api\Exception\InternalHttpException;

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
        $translates = $this->translate->onlyTranslate($content , array('target'=>$target));
        $translates = array_map(function ($v){
            $v['text'] = htmlspecialchars_decode(htmlspecialchars_decode($v['text'] , ENT_QUOTES) , ENT_QUOTES);
            return $v;
        } , $translates);
        return $this->response->array(array('translate'=>$translates , 'target'=>$target));
    }

    public function push(Request $request)
    {
        $type = $request->input('type');
        $userId = $request->input('user_id');
        $content = $request->input('content');
        switch ($type)
        {
            case 'privatechat':
                Jpush::dispatch('privateMessage' , '' , $userId , $content)->onQueue('op_npush');
                break;
            default:
                break;
        }
        return $this->response->noContent();
    }
    public function token()
    {
        $response = $this->response;
        if(auth()->check())
        {
            $user = auth()->user();
            $userId = $user->user_id;
            $name = $user->user_name;
            $avatar = $user->user_avatar_link;
            try{
                $token = app('rcloud')->getUser()->register(array(
                    'id'=> $userId,
                    'name'=> $name,
                    'portrait'=> $avatar
                ));
                if(empty($token))
                {
                    ry_server(true);
                    $token = app('rcloud')->getUser()->register(array(
                        'id'=> $userId,
                        'name'=> $name,
                        'portrait'=> $avatar
                    ));
                }
                throw_if($token['code']!=200 , new \Exception($token['code'].'===>'.$token['msg']));
            }catch (\Throwable $e)
            {
                $token = array(
                    'code'=>500,
                    'userId'=>$userId,
                    'message'=>$e->getMessage(),
                );
                \Log::error(\json_encode($token));
            }
            return $response->array($token);
        }
        return $response->noContent();
    }
}
