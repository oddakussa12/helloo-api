<?php

namespace App\Http\Controllers\V1;

use App\Jobs\Jpush;
use Illuminate\Http\Request;
use App\Services\JpushService;
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
        $content = $request->input('content' , '');
        $target = $request->input('target' , 'en');
        if(empty($content))
        {
            return $this->response->array(array('translate'=>$content , 'target'=>$target , 'origin'=>$target));
        }
        $languagesFile = 'google/languages.json';
        if(\Storage::exists($languagesFile))
        {
            $languages = \Storage::get($languagesFile);
            $languages = \json_decode($languages , true);
            if(!in_array($target , $languages))
            {
                return $this->response->array(array('translate'=>$content , 'target'=>$target , 'origin'=>$target));
            }
        }
        $originLang = $this->translate->detectLanguage($content);
        $translate = $this->translate->onlyTranslate($content , array('target'=>$target));
        return $this->response->array(array('translate'=>$translate , 'target'=>$target , 'origin'=>$originLang));
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
    
}
