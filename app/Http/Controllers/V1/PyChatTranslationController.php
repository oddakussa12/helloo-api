<?php

namespace App\Http\Controllers\V1;

use App\Models\PyChat;
use App\Models\PyChatTranslation;
use Illuminate\Http\Request;
use App\Services\TranslateService;
use App\Services\TencentTranslateService;
use App\Repositories\Contracts\PyChatTranslationRepository;
use App\Repositories\Contracts\PyChatRepository;
use Illuminate\Support\Facades\DB;

class PyChatTranslationController extends BaseController
{
    /**
     * @var PyChatTranslationRepository
     */
    private $pychattranslation;
    private $translate;
    private $pychat;

    /**
     * Display a listing of the resource.
     *
     * @param PyChatTranslationRepository $pychattranslation
     * @param TranslateService $translate
     */

    public function __construct(PyChatTranslationRepository $pychattranslation , TranslateService $translate,PyChatRepository $pychat)
    {
        $this->pychattranslation = $pychattranslation;
        $this->pychat = $pychat;
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
        $chat_uuid = $request->input('chat_uuid' , '');
        // 识别源语言
        if(empty($content))
        {
            $contentDefaultLang = $contentLang = 'en';
        }else{
            $contentLang = $this->translate->detectLanguage($content);
            $contentDefaultLang = $contentLang=='und'?'en':$contentLang;
        }
        //开启事务
        DB::beginTransaction();
        //判断content是否为数组翻译
        if(!is_array($content)){
            $pychat_array = array(
                    'from_id' => $request->input('from_id' , ''),
                    'to_id' => $request->input('to_id' , ''),
                    'chat_type' => $request->input('chat_type' , ''),
                    'chat_uuid' => $request->input('chat_uuid' , ''),
                    'chat_image' => $request->input('chat_image' , ''),
                    'chat_message_type' => $request->input('chat_message_type' , ''),
                    'chat_default_locale' => $contentDefaultLang,
                    'chat_ip' => getRequestIpAddress(),
            );
            // 执行主表存储
            $data = $this->chatinsert($chat_uuid,$pychat_array);
            if($request->input('chat_message_type' , '')=='image'){
                return $data;
            }
            //查询翻译信息
            $isInTran = DB::table('pychats_translations')->where('chat_uuid',$chat_uuid)->where('chat_locale',$target)->lockForUpdate()->first();
            //判断是否有翻译信息
            if(!empty($isInTran)){//是,直接返回内容
                $translation = $isInTran->chat_message;
                return $this->response->array(array('defaultlang'=>$contentDefaultLang,'translation'=>$translation,'chat_id'=>$isInTran->chat_id));
            }else{//否
                //准备存储翻译内容
                $translationArray = [
                    'chat_id'=> $data->chat_id,
                    'chat_uuid'=> $chat_uuid,
                    'chat_locale'=>$target,
                ];
                $translation = $this->executionTranslation($contentDefaultLang,$target,$content,$translationArray);
                //准备存储翻译后内容
                $translationArray['chat_message'] =$translation;
                //存储翻译内容
                $this->pychattranslation->store($translationArray);
                return $this->response->array(array('defaultlang'=>$contentDefaultLang,'translation'=>$translation,'chat_id'=>$data->chat_id));

            }
        }else{
            $chat_uuid = array_keys($content);
            $isInTran = DB::table('pychats_translations')->whereIn('chat_uuid',$chat_uuid)->where('chat_locale',$target)->lockForUpdate()->pluck('chat_message','chat_uuid');
            $isInTranKeys = $isInTran->keys()->toArray();
            $chat_uuid = array_diff($chat_uuid,$isInTranKeys);
            //翻译存在时处理
            foreach ($isInTranKeys as $key => $value) {
                $content[$value]['translation'] =$isInTran[$value];
            }
            //翻译不存在时处理
            foreach ($chat_uuid as $uuidkey => $uuidvalue) {
                $translationArray = [
                    'chat_id'=> $content[$uuidvalue]['chat_id'],
                    'chat_uuid'=> $uuidvalue,
                    'chat_locale'=>$target,
                ];
               $translation= $this->executionTranslation($content[$uuidvalue]['chat_default_locale'],$target,$content[$uuidvalue]['chat_default_message'],$translationArray);
               $content[$uuidvalue]['translation'] = $translation;
                //准备存储翻译后内容
                $translationArray['chat_message'] =$translation;
                //存储翻译内容
                $this->pychattranslation->store($translationArray);
            }
            //执行事务
            DB::commit();
            return $content;
        }

        // return $this->response->array(array('defaultlang'=>$contentDefaultLang,'translation'=>$translation));
    }
    public function chatinsert($chat_uuid,$pychat_array)
    {
        DB::beginTransaction();
        $pychatdata = DB::table('pychats')->where('chat_uuid',$chat_uuid)->lockForUpdate()->first();
        if(empty($pychatdata)){
            $pychatdata = $this->pychat->store($pychat_array);
        }
        //执行事务
        DB::commit();
        return $pychatdata;
    }
    public function executionTranslation($contentDefaultLang,$target,$content,$translationArray)
    {
        if((($contentDefaultLang=='zh-CN'&&$target=='en')||($contentDefaultLang=='en'&&$target=='zh-CN'))&&strlen(trim($content))<=1024)
            {//腾讯翻译
                $service = new TencentTranslateService();
                $zhlang = ['卧槽','简单来说'];
                $enlang = ['Fuck','tldr'];
                if($contentDefaultLang == 'en'&&$target=='zh-CN'){//判断是否有默认词条
                    foreach ($enlang as $enkey => $envalue) {
                        if(strcasecmp($content,$envalue)==0){
                            $translation = $zhlang[$enkey];
                            return $translation;
                        }else{//没有
                            $translation = $service->translate($content , array('source'=>$contentDefaultLang , 'target'=>$target));
                            if($translation===false)
                            {
                                $translation = $this->translate->pyChatTranslate($content , array('target'=>$target));
                            }
                            //返回客户端信息
                            return $translation;
                        }
                    }
                }else{
                    $translation = $service->translate($content , array('source'=>$contentDefaultLang , 'target'=>$target));
                        if($translation===false)
                        {
                            $translation = $this->translate->pyChatTranslate($content , array('target'=>$target));
                        }
                        return $translation;
                    }
            }else{//google翻译
                $translation = $this->translate->pyChatTranslate($content , array('target'=>$target));
                return $translation;
            }
    }
}
