<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RyChat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;
    public $now;

    public function __construct($data)
    {
        $this->data = $data;
        $now = Carbon::now();
        $this->now = $now->toDateTimeString();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $messageContent = array();
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
                    'RC:CmdMsg',
                    'RC:VCHangup',
                    'RC:VCAccept',
                    'RC:VCInvite',
                    'RC:VCRinging',
                    'Helloo:VideoMsg',
                    'Yooul:VideoLike'
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
                'filled',
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
        $now = Carbon::now()->toDateTimeString();
        if ($validator->fails()) {
            $data = array(
                'raw'    => \json_encode($raw , JSON_UNESCAPED_UNICODE),
                'errors' => \json_encode($validator->errors() , JSON_UNESCAPED_UNICODE),
                'created_at' => $now
            );
            DB::table('ry_chats_failed')->insert($data);
        } else {
            $messageContent['message_id'] = $raw['msgUID'];
            $messageContent['message_time'] = $raw['msgTimestamp'];
            $messageContent['message_type'] = $raw['objectName'];
            $msgTimestamp = $raw['msgTimestamp'];
            $index = Carbon::createFromTimestampMs($msgTimestamp)->format("Ym");
            $data = array(
                'chat_msg_uid'  => $raw['msgUID'],
                'chat_from_id'  => $raw['fromUserId'],
                'chat_to_id'    => $raw['toUserId'],
                'chat_msg_type' => $raw['objectName'],
                'chat_time'     => $msgTimestamp,
            );
            if(isset($raw['source'])) {
                $data['chat_source'] = $raw['source'];
            }
            if(isset($raw['content']))
            {
                $content = \json_decode($raw['content'] , true);

                if(isset($content['content']))
                {
                    $messageContent['message_content'] = $content['content'];
                }
                if(isset($content['imageUri']))
                {
                    $messageContent['message_content'] = $content['imageUri'];
                }
//                if(isset($content['user'])) {
//                    $data['chat_from_name'] = $content['user']['name'];
//                }
//                if(isset($content['user'])) {
//                    $data['chat_from_name'] = $content['user']['name'];
//                }

                if(isset($content['reason'])) {
                    $data['chat_extend'] = $content['reason'];
                }

                if(isset($content['mediaType'])) {
                    $data['chat_extend'] = $content['mediaType'];
                }

                if(isset($content['callId'])) {
                    $messageContent['message_content'] = $content['callId'];
                }

                if(isset($content['videoUrl'])) {
                    $messageContent['message_content'] = $content['videoUrl'];
                }
                if($messageContent['message_type']=='Yooul:VideoLike')
                {
                    $data['chat_extend'] = intval($content['LikeType']);
                    $messageContent['message_content'] = $content['videoID'];
                }
                if($messageContent['message_type']=='Helloo:VideoMsg')
                {
                    if(isset($content['user']['extra']))
                    {
                        $extra = \json_decode($content['user']['extra'] , true);
                        Log::info('$extra' , $extra);
                    }else{
                        $extra = array();
                    }
                    $video = array(
                        'message_id'=>$raw['msgUID'],
                        'video_url'=>isset($content['videoUrl'])?$content['videoUrl']:'',
                        'is_record'=>isset($extra['isRecord'])?intval($extra['isRecord']):0,
                        'voice_name'=>isset($extra['changeVoiceName'])?$extra['changeVoiceName']:'',
                        'bundle_name'=>isset($content['bundleName'])?$content['bundleName']:'',
                        'created_at'=>$this->now,
                    );
                    try{
                        DB::table('ry_video_messages_'.$index)->insert($video);
                    }catch (\Exception $e)
                    {
                        Log::info('insert_ry_video_message_fail' , array(
                            'code'=>$e->getCode(),
                            'message'=>$e->getMessage(),
                        ));
                    }
                }
                try{
                    $messageContent['created_at'] = $this->now;
                    DB::table('ry_messages_'.$index)->insert($messageContent);
                }catch (\Exception $e)
                {
                    Log::info('insert_ry_message_fail' , array(
                        'code'=>$e->getCode(),
                        'message'=>$e->getMessage(),
                    ));
                }
            }
            $data['chat_created_at'] = $this->now;
            DB::table('ry_chats_'.$index)->insert($data);
        }

    }

}
