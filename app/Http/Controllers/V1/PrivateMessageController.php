<?php

namespace App\Http\Controllers\V1;

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
        $content = $request->input('content' , '');
        $target = $request->input('target' , 'en');
        if(empty($content))
        {
            return $this->response->array(array('translate'=>$content , 'target'=>$target , 'origin'=>$target));
        }
        $originLang = $this->translate->detectLanguage($content);
        $translate = $this->translate->onlyTranslate($content , array('target'=>$target));
        return $this->response->array(array('translate'=>$translate , 'target'=>$target , 'origin'=>$originLang));
    }
    
}
