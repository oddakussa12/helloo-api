<?php

namespace App\Http\Controllers\V1;


use App\Jobs\RyChat;
use App\Models\RyChatFailed;
use Illuminate\Http\Request;
use App\Custom\Constant\Constant;
use App\Resources\UserCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Repositories\Contracts\UserRepository;

class RyChatController extends BaseController
{



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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
            Log::info('objectName' , array($objectName));
            if (in_array($objectName, array('RC:TxtMsg', 'RC:ImgMsg', 'RC:VcMsg' , 'RC:VCHangup' , 'RC:VCAccept' , 'RC:VCInvite' , 'RC:VCRinging' , 'Helloo:VideoMsg'))) {
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



    public function user()
    {
        $userIds = request()->input('user_ids' , '');
        $userIds = explode(',' , $userIds);
        $userIds = array_slice($userIds , 0 , 50);
        $users = app(UserRepository::class)->findByMany($userIds);
        return UserCollection::collection($users);
    }
}
