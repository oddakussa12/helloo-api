<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\TranslateService;
use Illuminate\Support\Facades\Redis;
use App\Services\TencentTranslateService;
use App\Repositories\Contracts\PyChatRepository;
use App\Repositories\Contracts\PyChatTranslationRepository;

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
     * @return array
     */
    public function store(Request $request)
    {
        $result = array();
        $content = $request->input('content' , '');
        $target = $request->input('target' , 'en');
        $chat_uuid = array_keys($content);
        $isInTran = DB::table('pychats_translations')->whereIn('chat_uuid',$chat_uuid)->where('chat_locale',$target)->pluck('chat_message','chat_uuid');
        $isInTranKeys = $isInTran->keys()->toArray();
        $chat_uuid = array_diff($chat_uuid,$isInTranKeys);
        //翻译存在时处理
        foreach ($isInTranKeys as  $uuid) {
            $result[$uuid]['translation'] = htmlspecialchars_decode(htmlspecialchars_decode($isInTran[$uuid] , ENT_QUOTES) , ENT_QUOTES);
        }
        $flag = 1;
        //翻译不存在时处理
        foreach ($chat_uuid as  $uuid) {
            $lock_key = 'cr_'.$uuid;
            if(Redis::set($lock_key, $flag, "nx", "ex", 60))
            {
                $translation = array(
                    'chat_id'=> $content[$uuid]['chat_id'],
                    'chat_uuid'=> $uuid,
                    'chat_locale'=>$target,
                );
                $contentTranslation= $this->executionTranslation($content[$uuid]['chat_default_locale'],$target,$content[$uuid]['chat_default_message']);
                $result[$uuid]['translation'] = htmlspecialchars_decode(htmlspecialchars_decode($contentTranslation , ENT_QUOTES) , ENT_QUOTES);
                //准备存储翻译后内容
                $translation['chat_message'] =$contentTranslation;
                //存储翻译内容
                DB::table('pychats_translations')->insert($translation);
                Redis::set($lock_key.'_c', $flag, "nx", "ex", 60);
            }else{
                $waitTime = time();
                while (true)
                {
                    $goTime = time();
                    if(Redis::get($lock_key.'_c')==$flag||$goTime-$waitTime>30)
                    {
                        $chat = DB::table('pychats_translations')->where('chat_uuid',$uuid)->first();
                        $result[$uuid]['translation'] = empty($chat)?'':$chat->chat_message;
                        break;
                    }
                }
            }
        }
        return $result;
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
    public function executionTranslation($contentDefaultLang,$target,$content)
    {
        if((($contentDefaultLang=='zh-CN'&&$target=='en')||($contentDefaultLang=='en'&&$target=='zh-CN'))&&strlen(trim($content))<=1024)
        {
            //腾讯翻译
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
