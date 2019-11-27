<?php

namespace App\Http\Controllers\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\TranslateService;
use App\Resources\PyChatCollection;
use Illuminate\Support\Facades\Redis;
use App\Services\TencentTranslateService;
use App\Repositories\Contracts\PyChatRepository;

class PyChatController extends BaseController
{
    /**
     * @var PyChatRepository
     */
    private $pychat;
    /**
     * @var TranslateService
     */
    private $translate;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct(PyChatRepository $pychat,TranslateService $translate)
    {
        $this->pychat = $pychat;
        $this->translate = $translate;
    }
    public function index()
    {
        //
        dd('index');
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
        $content = $request->input('content' , '');
        $target = $request->input('target' , 'en');
        $chat_type = $request->input('chat_type' , 'en');
        $chat_uuid = $request->input('chat_uuid' , '');
        $from_id = $request->input('from_id' , auth()->id());
        $to_id = $request->input('to_id' , '');
        $chat_image = $request->input('chat_image' , '');
        $chat_message_type = $request->input('chat_message_type' , '');
        $created_at = date('Y-m-d H:i:s',time());
        if(!empty($content)&&!empty($target)&&!empty($chat_uuid)&&!empty($from_id)&&!empty($to_id)&&!empty($chat_type)&&!empty($chat_message_type))
        {
            $contentLang = $this->translate->detectLanguage($content);
            $contentDefaultLang = $contentLang=='und'?'en':$contentLang;
            $pychat = array(
                'from_id'               =>$from_id,
                'to_id'                 =>$to_id,
                'chat_type'             =>$chat_type,
                'chat_uuid'             =>$chat_uuid,
                'chat_image'            =>$chat_image,
                'chat_default_locale'   => $contentDefaultLang,
                'chat_message_type'     => $chat_message_type,
                'chat_ip'               =>getRequestIpAddress(),
                'chat_created_at'       =>$created_at,
                'chat_updated_at'       =>$created_at,
            );
            $chat = $this->chatStore($chat_uuid,$pychat);
            if($chat_message_type!='text'){
                return $this->response->array($chat);
            }
            $translation = $this->chatTranslationStore($chat , $contentDefaultLang , $content , $target);
            $chat_id = $chat['chat_id'];
            $chatTime = optional($chat['chat_created_at'])->toDateTimeString();
        }else{
            $translation = $content;
            $chat_id = '';
            $chatTime = $created_at;
        }
        return $this->response->array(
            array(
                'defaultLang'=>$contentDefaultLang,
                'translation'=>htmlspecialchars_decode(htmlspecialchars_decode($translation , ENT_QUOTES) , ENT_QUOTES) ,
                'chat_id'=>$chat_id ,
                'created_at'=>$chatTime
            )
        );

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
        $this->pychat->find($id)->delete();
    }

    public function showMessageByUserId(Request $request)
    {
        return $this->pychat->showMessageByUserId('28464');
    }

    public function showMessageByRoomUuid(Request $request)
    {
        return PyChatCollection::Collection($this->pychat->limitMessage($request->input('chat_id' , ''),$request->input('room_uuid' , '')));
    }

    public function chatStore($chat_uuid,$pychat)
    {
        $flag = 1;
        $lock_key = 'c_'.$chat_uuid;
        if(Redis::set($lock_key, $flag, "nx", "ex", 100))
        {
            $chat_id = DB::table('pychats')->insertGetId($pychat);
            $chat_time = $pychat['chat_created_at'];
            $chat_uuid = $pychat['chat_uuid'];
            Redis::set($lock_key.'_c', $flag, "nx", "ex", 100);
        }else{
            while (true)
            {
                if(Redis::get($lock_key.'_c')==$flag)
                {
                    $chat = DB::table('pychats')->where('chat_uuid',$chat_uuid)->first();
                    $chat_id = $chat->chat_id;
                    $chat_time = optional($chat->chat_created_at)->toDateTimeString();
                    break;
                }
            }
        }
        return array('chat_id'=>$chat_id , 'chat_uuid'=>$chat_uuid , 'chat_created_at'=>$chat_time);
    }


    protected function chatTranslationStore($chat , $contentDefaultLang , $content , $target)
    {
        $flag = 1;
        $contentTranslation = '';
        $chat_uuid = $chat['chat_uuid'];
        $lock_key_pre = 'ct_'.$chat_uuid.'_';
        $defaultChat = DB::table('pychats_translations')->where('chat_uuid',$chat_uuid)->where('chat_locale',$contentDefaultLang)->first();
        if(empty($defaultChat))
        {
            $lock_key = $lock_key_pre.$contentDefaultLang;
            if(Redis::set($lock_key, $flag, "nx", "ex", 60))
            {
                $defaultTranslation = [
                    'chat_id'=> $chat['chat_id'],
                    'chat_uuid'=> $chat_uuid,
                    'chat_locale'=>$contentDefaultLang,
                    'chat_message'=>$content
                ];
                DB::table('pychats_translations')->insert($defaultTranslation);
                Redis::set($lock_key.'_c', $flag, "nx", "ex", 60);
            }
        }

        $lock_key = $lock_key_pre.$target;
        if(Redis::set($lock_key, $flag, "nx", "ex", 100))
        {
            $translation = [
                'chat_id'=> $chat['chat_id'],
                'chat_uuid'=> $chat_uuid,
                'chat_locale'=>$target,
            ];
            $contentTranslation = $this->executionTranslation($contentDefaultLang,$target,$content);
            $translation['chat_message'] =$contentTranslation;
            DB::table('pychats_translations')->insert($translation);
            Redis::set($lock_key.'_c', $flag, "nx", "ex", 100);
        }else{
            $waitTime = time();
            while(true)
            {
                $goTime = time();
                if(Redis::get($lock_key.'_c')==$flag||$goTime-$waitTime>30)
                {
                    $translation = DB::table('pychats_translations')->where('chat_uuid',$chat_uuid)->where('chat_locale',$target)->first();
                    if(empty($translation))
                    {
                        $contentTranslation = $content;
                    }else{
                        $contentTranslation = $translation->chat_message;
                    }
                    break;
                }
            }
        }
        return $contentTranslation;
    }

    public function chatImageStore(Request $request)
    {
        $created_at = date('Y-m-d H:i:s',time());
        $chat_type = $request->input('chat_type' , 'en');
        $chat_uuid = $request->input('chat_uuid' , '');
        $from_id = $request->input('from_id' , auth()->id());
        $to_id = $request->input('to_id' , '');
        $chat_image = $request->input('chat_image' , '');
        $chat_message_type = $request->input('chat_message_type' , '');
        $pychat = array(
            'from_id' => $from_id,
            'to_id' => $to_id,
            'chat_type' => $chat_type,
            'chat_uuid' => $chat_uuid,
            'chat_image' => $chat_image,
            'chat_message_type' => $chat_message_type,
            'chat_ip' => getRequestIpAddress(),
            'chat_created_at'=>$created_at,
            'chat_updated_at'=>$created_at,
        );
        $chat = $this->chatStore($chat_uuid,$pychat);
        $chat['created_at'] = Carbon::parse($chat['chat_created_at'])->diffForHumans();
        return $this->response->array($chat);
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
