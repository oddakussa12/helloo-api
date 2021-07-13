<?php

namespace App\Http\Controllers\V1;


use App\Jobs\RyChat;
use App\Models\RyChatFailed;
use Illuminate\Http\Request;
use App\Custom\Constant\Constant;
use Illuminate\Support\Facades\Redis;

class RyChatController extends BaseController
{
    /**
     * @note 融云消息
     * @datetime 2021-07-12 19:01
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function store(Request $request)
    {
        $all = $request->all();
        $response = $this->response->noContent();
        $rule = [
            'msgUID' => [
                'required',
            ]
        ];
        $validator = \Validator::make($all, $rule);
        if ($validator->fails()) {
            $data = array(
                'raw'=>\json_encode($all , JSON_UNESCAPED_UNICODE),
                'errors'=>\json_encode($validator->errors() , JSON_UNESCAPED_UNICODE)
            );
            RyChatFailed::create($data);
        }else{
            $objectName = $request->input('objectName' , '');
            if (in_array($objectName, array('RC:TxtMsg', 'RC:ImgMsg', 'RC:VcMsg' , 'RC:VCHangup' , 'RC:VCAccept' , 'RC:VCInvite' , 'RC:VCRinging' , 'Helloo:VideoMsg' , 'Yooul:VideoLike' , 'Helloo:VoiceMsg' , "Helloo:GoodsMsg"))) {
                $msgUID   = $request->input('msgUID', '');
                $lock_key = 'ry_room_chat_'.$msgUID;
                if(Redis::set($lock_key, 1, "nx", "ex", 15))
                {
                    if (Constant::QUEUE_PUSH_TYPE == 'redis') {
                        if (Constant::QUEUE_RY_CHAT_SWITCH) {
                            $ryChat = new RyChat($all);
                            $this->dispatch($ryChat->onQueue("helloo_{store_ry_msg}"));
                        }
                    } else {
                        if (Constant::QUEUE_RY_CHAT_SWITCH) {
                            $ryChat = new RyChat($all);
                            $this->dispatch($ryChat->onConnection('sqs')->onQueue(Constant::QUEUE_RY_CHAT));
                        }
                    }
                }
            }
        }
        return $response->setStatusCode(200);
    }
}
