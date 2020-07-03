<?php

namespace App\Http\Controllers\V1;

use App\Models\App;
use App\Services\NiuTranslateService;
use App\Services\TranslateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;


class TranslationController extends BaseController
{

    public function index(Request $request)
    {
        $content = strval($request->input('content' , 'hello'));
        $target = $request->input('target' , 'en');
        $type = $request->input('type' , 'g');
        switch ($type)
        {
            case 'n':
                $result = app(NiuTranslateService::class)->onlyTranslate($content , array('target'=>$target));
                break;
            case 'f':
            case 'w':
                $result = '';
                break;
            default:
                $google = app(TranslateService::class)->onlyTranslate((array)$content , array('target'=>$target));
                $result = array('source'=>$google[0]['source'] , 'target'=>$target , 'translate'=>$google[0]['text']);
                break;
        }
        return $this->response->array($result);
    }



}
