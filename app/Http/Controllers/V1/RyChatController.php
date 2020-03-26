<?php

namespace App\Http\Controllers\V1;

use App\Jobs\RyChat;
use Ramsey\Uuid\Uuid;
use App\Models\RyRoomChat;
use Illuminate\Http\Request;
use App\Services\TranslateService;
use Illuminate\Support\Facades\Redis;
use App\Resources\RyRoomChatCollection;

class RyChatController extends BaseController
{
    /**
     * @var TranslateService
     */
    private $translate;
    /**
     * Display a listing of the resource.
     *
     * @param TranslateService $translateService
     */

    public function __construct(TranslateService $translateService)
    {
        $this->translate = $translateService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

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
        $response = $this->response->noContent();
        $device = new RyChat($request->all());
        $this->dispatch($device->onQueue('store_ry_msg'));
        return $response->setStatusCode(200);
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

    }

    public function roomChatTranslation(Request $request)
    {
        $postKey = 'room.chat.data';
        $room_chats = (array)($request->input('room_chats', array()));
        $room_chat_ids = array_keys($room_chats);
        $locale = strval($request->input('locale', locale()));
        $needTranslationChatIds = array();
        $needTranslationChatContents = array();
        foreach ($room_chat_ids as $id)
        {
            $field = 'room_chat_id_' . $id;
            $chatTranslations = Redis::hget($postKey , $field);
            if($chatTranslations===null)
            {
                Redis::hset($postKey , $field , collect(array()));
                $chatTranslations = Redis::hget($postKey , $field);
            }
            $chatTranslationState = \json_decode($chatTranslations);
            if(!in_array($locale , $chatTranslationState))
            {
                array_push($needTranslationChatIds , $id);
                array_push($needTranslationChatContents , $room_chats[$id]);
            }
        }
        $roomChatTranslations = array();
        if(!empty($needTranslationChatContents))
        {
            $translations = $this->translate->translateBatch($needTranslationChatContents);
            foreach ($translations as $translation)
            {
                $key = array_search($translation['input'] , $room_chats);
                if($key!==false)
                {
                    $roomChatTranslations[$key] = $translation['text'];
                }
            }
        }
        $ryRoomChat = new RyRoomChat();
        $ryRoomChats = $ryRoomChat->whereIn('room_chat_id' , $room_chat_ids)->with('translations')->get();
        foreach ($ryRoomChats as $ryRoomChat)
        {
            $room_chat_id = $ryRoomChat->room_chat_id;
            if(in_array($room_chat_id , $needTranslationChatIds))
            {
                $field = 'room_chat_id_' . $room_chat_id;
                $chatTranslationState = \json_decode(Redis::hget($postKey , $field));
                $ryRoomChat->fill(array("{$locale}"=>array('room_chat_content'=>$roomChatTranslations[$room_chat_id])));
                $ryRoomChat->save();
                array_push($chatTranslationState , $locale);
                Redis::hset($postKey , $field , collect($chatTranslationState));
            }
        }
       return RyRoomChatCollection::collection($ryRoomChats);
    }

    public function showByRoom(Request $request)
    {
        $ryRoomChat = new RyRoomChat();
        $ryRoomChat = $ryRoomChat->with('translations')->with('user')->orderBy('room_chat_id' , 'DESC')->limit(5);
        $last_chat_id = intval($request->input('last_chat_id' , 0));
        if($last_chat_id>0)
        {
            $ryRoomChat = $ryRoomChat->where('room_chat_id', '<' , $last_chat_id);
        }
        return RyRoomChatCollection::collection($ryRoomChat->get());
    }

    public function storeRoomChat(Request $request)
    {
        $room_chat_uuid = Uuid::uuid1()->toString();
        $room_id = intval($request->input('room_id' , 1));
        $room_uuid = intval($request->input('room_uuid' , ''));
        $room_from_id = intval($request->input('room_from_id' , 0));
        $room_chat_type = strval($request->input('room_chat_type' , 'text'));
        $room_chat_image = strval($request->input('room_chat_image' , ''));
        $room_chat_content = strval($request->input('room_chat_content' , ''));
        $roomChatData = array(
            'room_id'=>$room_id,
            'room_uuid'=>$room_uuid,
            'room_chat_uuid'=>$room_chat_uuid,
            'room_from_id'=>$room_from_id,
            'room_chat_type'=>$room_chat_type,
            'room_chat_image'=>$room_chat_image,
//            'room_chat_default_locale'=>$room_chat_image,
            'room_chat_ip'=>getRequestIpAddress(),
        );
        if($room_chat_type=='text'&&!empty($room_chat_content))
        {
            $room_chat_default_locale = $this->translate->detectLanguage($room_chat_content);
            $roomChatData[$room_chat_default_locale] = array('room_chat_content'=>$room_chat_content);
            $roomChatData['room_chat_default_locale'] = $room_chat_default_locale;
        }
        RyRoomChat::create($roomChatData);

        $RyRoomChat = RyRoomChat::where('room_chat_uuid' , $room_chat_uuid)->select('room_chat_id')->first();

        $chatKey = 'room.chat.data';
        $field = 'room_chat_id_' . $RyRoomChat->room_chat_id;
        $chatData = collect(array($room_chat_default_locale));
        Redis::hset($chatKey , $field , $chatData);
        return $this->response->noContent();
    }
}
