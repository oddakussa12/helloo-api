<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use App\Models\PyChat;
use Illuminate\Http\Request;
use App\Services\TranslateService;
use Illuminate\Support\Facades\DB;
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
        $flag = 1;
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
        $pychat_array = array(
            'from_id' => $request->input('from_id' , ''),
            'to_id' => $request->input('to_id' , ''),
            'chat_type' => $request->input('chat_type' , ''),
            'chat_uuid' => $request->input('chat_uuid' , ''),
            'chat_image' => $request->input('chat_image' , ''),
            'chat_message_type' => $request->input('chat_message_type' , ''),
            'chat_default_locale' => $contentDefaultLang,
            'chat_ip' => getRequestIpAddress(),
            'chat_created_at'=>date('Y-m-d H:i:s',time()),
            'chat_updated_at'=>date('Y-m-d H:i:s',time()),
        );
            $lock_key = $chat_uuid.'_'.$target;
            // 执行主表存储
            $chat = $this->chatinsert($chat_uuid,$pychat_array);
            if($request->input('chat_message_type' , '')=='image'){
                return $chat;
            }
            $isInTran = DB::table('pychats_translations')->where('chat_uuid',$chat_uuid)->where('chat_locale',$target)->first();
            if(empty($isInTran))
            {
                if(Redis::set($lock_key, $flag, "nx", "ex", 100))
                {
                    $translationArray = [
                        'chat_id'=> $chat['chat_id'],
                        'chat_uuid'=> $chat_uuid,
                        'chat_locale'=>$target,
                    ];
                    $translation = $this->executionTranslation($contentDefaultLang,$target,$content,$translationArray);

                    //准备存储翻译后内容
                    $translationArray['chat_message'] =$translation.$target;
                    //存储翻译内容

                    DB::table('pychats_translations')->insert($translationArray);
                    Redis::set($lock_key.'_c', $flag, "nx", "ex", 100);
                }else{
                    $waitTime = time();
                    while(true)
                    {
                        $goTime = time();
                        if(Redis::get($lock_key.'_c')===$flag||$goTime-$waitTime>30)
                        {
                            $isInTran = DB::table('pychats_translations')->where('chat_uuid',$chat_uuid)->where('chat_locale',$target)->first();
                            if(empty($isInTran))
                            {
                                $translation = $content;
                            }else{
                                $translation = $isInTran->chat_message;
                            }
                            break;
                        }
                    }

                }
            }else{
                $translation = $isInTran->chat_message;
            }
            return $this->response->array(array('defaultlang'=>$contentDefaultLang,'translation'=>$translation , 'chat_id'=>$chat['chat_id'] , 'created_at'=>Carbon::parse($chat['chat_created_at'])->diffForHumans()));

        // return $this->response->array(array('defaultlang'=>$contentDefaultLang,'translation'=>$translation));
    }
    public function chatImageInsert(Request $request)
    {
        $chat_uuid = $request->input('chat_uuid' , '');
        $pychat_array = array(
            'from_id' => $request->input('from_id' , ''),
            'to_id' => $request->input('to_id' , ''),
            'chat_type' => $request->input('chat_type' , ''),
            'chat_uuid' => $request->input('chat_uuid' , ''),
            'chat_image' => $request->input('chat_image' , ''),
            'chat_message_type' => $request->input('chat_message_type' , ''),
            'chat_default_locale' => 'en',
            'chat_ip' => getRequestIpAddress(),
            'chat_created_at'=>date('Y-m-d H:i:s',time()),
            'chat_updated_at'=>date('Y-m-d H:i:s',time()),
        );

        // 执行主表存储
        $chat = $this->chatinsert($chat_uuid,$pychat_array);
        $chat['created_at']=Carbon::parse($chat['chat_created_at'])->diffForHumans();
        return $chat;
    }
    public function chatinsert($chat_uuid,$pychat_array)
    {
        DB::beginTransaction();
        $chat = DB::table('pychats')->where('chat_uuid',$chat_uuid)->lockForUpdate()->first();
        if(empty($chat)){
            $chat_id = DB::table('pychats')->insertGetId($pychat_array);
            $chat_time = $pychat_array['chat_created_at'];
        }else{
            $chat_id = $chat->chat_id;
            $chat_time = $chat->chat_created_at;
        }
        DB::commit();
        return array('chat_id'=>$chat_id , 'chat_created_at'=>$chat_time);
    }
    public function messageListTranslate(Request $request)
    {
        $content = $request->input('content' , '');
        $target = $request->input('target' , 'en');
        $chat_uuid = $request->input('chat_uuid' , '');
        $chat_uuid = array_keys($content);
        DB::beginTransaction();
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
           DB::table('pychats_translations')->insert($translationArray);
        }
        //执行事务
        DB::commit();
        return $content;
    }
    public function executionTranslation($contentDefaultLang,$target,$content,$translationArray)
    {
        return date('Y-m-d H:i:s').time().$content;
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
