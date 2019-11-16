<?php

namespace App\Http\Controllers\V1;

use App\Models\PyChat;
use Illuminate\Http\Request;
use App\Services\TranslateService;
use App\Services\TencentTranslateService;
use App\Repositories\Contracts\PyChatTranslationRepository;

class PyChatTranslationController extends BaseController
{
    /**
     * @var PyChatTranslationRepository
     */
    private $pychattranslation;
    private $translate;

    /**
     * Display a listing of the resource.
     *
     * @param PyChatTranslationRepository $pychattranslation
     * @param TranslateService $translate
     */

    public function __construct(PyChatTranslationRepository $pychattranslation , TranslateService $translate)
    {
        $this->pychattranslation = $pychattranslation;
        $this->translate = $translate;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(!PyChat::find($request->chat_id)->hasTranslation($request->chat_locale)){
            $pychattranslation_array = array(
                'chat_id'=> $request->chat_id,
                'chat_locale'=>$request->chat_locale,
                'chat_message'=>$request->chat_message,
            );
            return $this->pychattranslation->store($pychattranslation_array);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function translate(Request $request)
    {
        $content = $request->input('content' , '');
        $target = $request->input('target' , 'en');
        if(empty($content))
        {
            $contentDefaultLang = $contentLang = 'en';
        }else{
            $contentLang = $this->translate->detectLanguage($content);
            $contentDefaultLang = $contentLang=='und'?'en':$contentLang;
        }
        if((($contentDefaultLang=='zh-CN'&&$target=='en')||($contentDefaultLang=='en'&&$target=='zh-CN'))&&strlen(trim($content))<=1024)
        {
            $service = new TencentTranslateService();
            $zhlang = ['卧槽','简单来说'];
            $enlang = ['Fuck','tldr'];
            if($contentDefaultLang == 'zh-CN'){
                foreach ($zhlang as $zhkey => $zhvalue) {
                    if($content == $zhvalue){
                        $translation = $enlang[$zhkey];
                    }
                }
            }else if($contentDefaultLang == 'en'){
                foreach ($enlang as $enkey => $envalue) {
                    if(strcasecmp($content,$envalue)==0){
                        $translation = $zhlang[$enkey];
                    }
                }
            }else{
                $translation = $service->translate($content , array('source'=>$contentDefaultLang , 'target'=>$target));
                if($translation===false)
                {
                    $translation = $this->$translate->pyChatTranslate($content , array('target'=>$target));
                }
            }
        }else{
            $translation = $this->translate->pyChatTranslate($content , array('target'=>$target));
        }
        return $this->response->array(array('defaultlang'=>$contentDefaultLang,'translation'=>$translation));
    }
}
