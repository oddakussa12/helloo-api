<?php

namespace App\Jobs;

use App\Models\RyChatRaw;
use App\Models\RyChatFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Validation\Rule;
use App\Models\RyChat as RyChats;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RyChat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $raw = $this->data;
        $rule = [
            'fromUserId' => [
                'required',
            ],
            'toUserId' => [
                'required',
            ],
            'objectName' => [
                'required',
                Rule::in([
                    'RC:TxtMsg',
                    'RC:VcMsg',
                    'RC:HQVCMsg',
                    'RC:ImgMsg',
                    'RC:GIFMsg',
                    'RC:ImgTextMsg',
                    'RC:FileMsg',
                    'RC:LBSMsg',
                    'RC:SightMsg',
                    'RC:CombineMsg',
                    'RC:PSImgTxtMsg',
                    'RC:PSMultiImgTxtMsg',
                    'RC:SRSMsg',
                ]),
            ],
            'content' => [
                'required',
            ],
            'channelType' => [
                'required',
                Rule::in(['PERSON' , 'TEMPGROUP' , 'PRIVATE', 'GROUP', 'CHATROOM', 'CUSTOMER_SERVICE', 'SYSTEM', 'APP_PUBLIC_SERVICE', 'PUBLIC_SERVICE']),
            ],
            'msgTimestamp' => [
                'required'
            ],
            'msgUID' => [
                'required',
            ],
            'sensitiveType' => [
                'required',
                Rule::in([0 , 1 , 2])
            ],
            'source' => [
                'filled',
            ],
            'groupUserIds' => [
                'filled',
            ],
        ];
        $validator = \Validator::make($raw, $rule);
        if ($validator->fails()) {
            $data = array(
                'raw'=>\json_encode($raw , JSON_UNESCAPED_UNICODE),
                'errors'=>\json_encode($validator->errors() , JSON_UNESCAPED_UNICODE)
            );
            RyChatFailed::create($data);
        }else{
            $data = array(
                'chat_msg_uid'=>$raw['msgUID'],
                'chat_from_id'=>$raw['fromUserId'],
                'chat_to_id'=>$raw['toUserId'],
                'chat_msg_type'=>$raw['objectName'],
                'chat_channel_type'=>$raw['channelType'],
                'chat_time'=>$raw['msgTimestamp'],
                'chat_sensitive_type'=>$raw['sensitiveType']
            );
            if(isset($raw['source']))
            {
                $data['chat_source'] = $raw['source'];
            }
            if(isset($raw['groupUserIds']))
            {
                $data['chat_group_to_id'] = $raw['groupUserIds'];
            }
            if(isset($raw['content']))
            {
                $content = \json_decode($raw['content'] , true);
                if(isset($content['content']))
                {
                    $data['chat_content'] = $content['content'];
                }
                if(isset($content['imageUri']))
                {
                    $data['chat_image'] = $content['imageUri'];
                }
                if(isset($content['user']))
                {
                    $data['chat_from_name'] = $content['user']['name'];
                }
                if(isset($content['user']['extra']))
                {
                    $data['chat_from_extra'] = \json_encode($content['user']['extra'] , JSON_UNESCAPED_UNICODE);
                }
            }
            $ryChat = RyChats::create($data);

            RyChatRaw::create(array('chat_id'=>$ryChat->chat_id , 'raw'=>\json_encode($raw , JSON_UNESCAPED_UNICODE),'chat_time'=>$raw['msgTimestamp']));

        }

    }
}
