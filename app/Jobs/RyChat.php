<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Redis;
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

    public $index;
    public $day;
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
                    'Yooul:VideoLike',
                    'Helloo:VoiceMsg'
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
            $this->index = $index = Carbon::createFromTimestampMs($msgTimestamp)->format("Ym");
            $this->day = Carbon::createFromTimestampMs($msgTimestamp)->format("Y-m-d");
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

            $content = \json_decode($raw['content'] , true);

            if(isset($content['content'])) //txt message
            {
                $messageContent['message_content'] = $content['content'];
            }
            if(isset($content['imageUri'])) //image message
            {
                $messageContent['message_content'] = $content['imageUri'];
            }
            if(isset($content['callId'])) { //call message
                $messageContent['message_content'] = $content['callId'];
            }
            if(isset($content['videoUrl'])) { //video message
                $messageContent['message_content'] = $content['videoUrl'];
            }
            if(isset($content['videoID'])) { //like message
                $messageContent['message_content'] = $content['videoID'];
            }
            if(isset($content['uri'])) { //audio message
                $messageContent['message_content'] = $content['uri'];
            }

            if(isset($content['reason'])) { //video call or audio call reason
                $data['chat_extend'] = $content['reason'];
            }
            if(isset($content['mediaType'])) { //video call or audio call type
                $data['chat_extend'] = $content['mediaType'];
            }
            if(isset($content['LikeType'])) { //like
                $data['chat_extend'] = intval($content['LikeType']);
            }

            $data['chat_created_at'] = $this->now;
            DB::table('ry_chats_'.$index)->insert($data);

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

            if($messageContent['message_type']=='RC:TxtMsg')
            {
                $this->handleTxt($raw['fromUserId'] , $raw['toUserId']);
            }

            if($messageContent['message_type']=='Helloo:VideoMsg')
            {
                if(isset($content['user']['extra']))
                {
                    $extra = \json_decode($content['user']['extra'] , true);
                }else{
                    $extra = array();
                }
                $messageId = $raw['msgUID'];
                $videoUrl = isset($content['videoUrl'])?$content['videoUrl']:'';
                $isRecord = isset($extra['isRecord'])?intval($extra['isRecord']):0;
                $voiceName = isset($extra['changeVoiceName'])?$extra['changeVoiceName']:'';
                $bundleName = isset($content['bundleName'])&&$content['bundleName']!='none'?$content['bundleName']:'';
                $this->handleVideo($raw['fromUserId'] , $raw['toUserId'] , $messageId , $videoUrl , $isRecord , $voiceName , $bundleName);
            }

            if($messageContent['message_type']=='Helloo:VoiceMsg')
            {
                $audioUrl = isset($content['uri'])?$content['uri']:'';
                $duration = isset($content['duration'])?$content['duration']:0;
                $this->handleVoice($raw['msgUID'] , $audioUrl , $duration);
            }

            if($messageContent['message_type']=='Yooul:VideoLike')
            {
                $likeId = isset($content['videoID'])?$content['videoID']:'';
                $this->handleVideoLike($raw['msgUID'] , $raw['fromUserId'] , $raw['toUserId'] , $likeId);
            }

        }

    }

    private function handleTxt($from , $to)
    {
        $hashKey = "helloo:message:service:mutual-txt-".$this->day;
        $setKey = "helloo:message:service:mutual-txt-geq-ten".$this->day;
        $lock_key = "helloo:message:service:unique-txt-".strval($from).'-'.strval($to);
        if(!Redis::exists($lock_key))
        {
            Redis::set($lock_key , 1);
            Redis::expire($lock_key , 600);
            $flag = DB::table('ry_chats_logs')->where('from' , $from)->where('to' , $to)->where('type' , 'txt')->first();
            if(blank($flag))
            {
                DB::table('ry_chats_logs')->insert(array(
                    'from'=>$from,
                    'to'=>$to,
                    'type'=>'txt',
                    'created_at'=>$this->now,
                ));
                $counts = DB::table('ry_messages_counts')->where('user_id' , $from)->first();
                if(blank($counts))
                {
                    $id = DB::table('ry_messages_counts')->insertGetId(array(
                        'user_id'=>$from,
                        'txt'=>1,
                        'created_at'=>$this->now,
                        'updated_at'=>$this->now,
                    ));
                }else{
                    $id = $counts->id;
                    DB::table('ry_messages_counts')->where('id' , $id)->increment('video' , 1 , array(
                        'updated_at'=>$this->now,
                    ));
                }
                MoreTimeUserScoreUpdate::dispatch($from , 'firstTxtMessage' , $id)->onQueue('helloo_{more_time_user_score_update}');
            }
        }
        $fc = Redis::hincrby($hashKey , strval($from).'-'.strval($to) , 1);
        if($fc==10)
        {
            $cf = Redis::hget($hashKey , strval($to).'-'.strval($from));
            if($cf>=10)
            {
                if(!Redis::sismember($setKey , $from))
                {
                    Redis::sadd($setKey , $from);
                    DB::table('ry_mutual_messages')->insertGetId(array(
                        'from_id'=>$from,
                        'to_id'=>$to,
                        'type'=>'txt',
                        'time'=>$this->day,
                        'created_at'=>$this->now,
                    ));
                    MoreTimeUserScoreUpdate::dispatch($from , 'tenVideoMessage')->onQueue('helloo_{more_time_user_score_update}');
                }
                if(!Redis::sismember($setKey , $to))
                {
                    Redis::sadd($setKey , $to);
                    DB::table('ry_mutual_messages')->insert(array(
                        'from_id'=>$to,
                        'to_id'=>$from,
                        'type'=>'txt',
                        'time'=>$this->day,
                        'created_at'=>$this->now,
                    ));
                    MoreTimeUserScoreUpdate::dispatch($to , 'tenVideoMessage')->onQueue('helloo_{more_time_user_score_update}');
                }
            }
        }
    }

    private function handleVideo($from , $to  , $messageId , $videoUrl , $isRecord , $voiceName , $bundleName)
    {
        $hashKey = "helloo:message:service:mutual-video-".$this->day;
        $setKey = "helloo:message:service:mutual-video-geq-ten".$this->day;
        $lock_key = "helloo:message:service:unique-video-".strval($from).'-'.strval($to);
        if(!Redis::exists($lock_key))
        {
            Redis::set($lock_key , 1);
            Redis::expire($lock_key , 600);
            $flag = DB::table('ry_chats_logs')->where('from' , $from)->where('to' , $to)->where('type' , 'video')->first();
            if(blank($flag))
            {
                DB::table('ry_chats_logs')->insert(array(
                    'from'=>$from,
                    'to'=>$to,
                    'type'=>'video',
                    'created_at'=>$this->now,
                ));
                $counts = DB::table('ry_messages_counts')->where('user_id' , $from)->first();
                if(blank($counts))
                {
                    $id = DB::table('ry_messages_counts')->insertGetId((array(
                        'user_id'=>$from,
                        'video'=>1,
                        'created_at'=>$this->now,
                        'updated_at'=>$this->now,
                    )));
                }else{
                    $id = $counts->id;
                    DB::table('ry_messages_counts')->where('id' , $id)->increment('video' , 1 , array(
                        'updated_at'=>$this->now,
                    ));
                }
                MoreTimeUserScoreUpdate::dispatch($from , 'firstVideoMessage' , $id)->onQueue('helloo_{more_time_user_score_update}');
            }
        }
        $fc = Redis::hincrby($hashKey , strval($from).'-'.strval($to) , 1);
        if($fc==10)
        {
            $cf = Redis::hget($hashKey , strval($to).'-'.strval($from));
            if($cf>=10)
            {
                if(!Redis::sismember($setKey , $from))
                {
                    Redis::sadd($setKey , $from);
                    DB::table('ry_mutual_messages')->insertGetId(array(
                        'from_id'=>$from,
                        'to_id'=>$to,
                        'type'=>'video',
                        'time'=>$this->day,
                        'created_at'=>$this->now,
                    ));
                    MoreTimeUserScoreUpdate::dispatch($from , 'tenVideoMessage' , $to)->onQueue('helloo_{more_time_user_score_update}');
                }
                if(!Redis::sismember($setKey , $to))
                {
                    Redis::sadd($setKey , $to);
                    DB::table('ry_mutual_messages')->insert(array(
                        'from_id'=>$to,
                        'to_id'=>$from,
                        'type'=>'video',
                        'time'=>$this->day,
                        'created_at'=>$this->now,
                    ));
                    MoreTimeUserScoreUpdate::dispatch($to , 'tenVideoMessage' , $from)->onQueue('helloo_{more_time_user_score_update}');
                }
            }
        }
        Redis::expireAt($lock_key , Carbon::createFromFormat('Y-m-d' , $this->day)->endOfDay()->addMinutes(15)->timestamp);
        $video = array(
            'message_id'=>$messageId,
            'video_url'=>$videoUrl,
            'is_record'=>$isRecord,
            'voice_name'=>$voiceName,
            'bundle_name'=>$bundleName,
            'created_at'=>$this->now,
        );
        try{
            DB::table('ry_video_messages_'.$this->index)->insert($video);
        }catch (\Exception $e)
        {
            Log::info('insert_ry_video_message_fail' , array(
                'code'=>$e->getCode(),
                'message'=>$e->getMessage(),
            ));
        }
    }

    private function handleVoice($messageId , $audioUrl , $duration )
    {
        $audio = array(
            'message_id'=>$messageId,
            'audio_url'=>$audioUrl,
            'duration'=>$duration,
            'created_at'=>$this->now,
        );
        try{
            DB::table('ry_audio_messages_'.$this->index)->insert($audio);
        }catch (\Exception $e)
        {
            Log::info('insert_ry_audio_message_fail' , array(
                'code'=>$e->getCode(),
                'message'=>$e->getMessage(),
            ));
        }
    }


    private function handleVideoLike($messageId , $from , $to , $likeId)
    {
        if(blank($likeId))
        {
            return;
        }
        $lock_key = "helloo:message:service:unique-video-like-".strval($from).'-'.strval($to).'-'.$likeId;
        if(!Redis::exists($lock_key))
        {
            Redis::set($lock_key , 1);
            Redis::expire($lock_key , 600);
            $like = DB::table('ry_like_messages')->where('from_id' , $from)->where('to_id' , $to)->where('liked_id' , $likeId)->first();
            if(blank($like))
            {
                $likeCount = DB::table('ry_messages_counts')->where('user_id' , $from)->first();
                if(blank($likeCount))
                {
                    DB::table('ry_messages_counts')->insert(array(
                        'user_id'=>$from,
                        'like'=>1,
                        'created_at'=>$this->now,
                        'updated_at'=>$this->now,
                    ));
                }else{
                    DB::table('ry_messages_counts')->where('user_id' , $from)->increment('like' , 1 , array(
                        'updated_at'=>$this->now,
                    ));
                }
                MoreTimeUserScoreUpdate::dispatch($from , 'likeVideo' , $likeId)->onQueue('helloo_{more_time_user_score_update}');
                $likedCount = DB::table('ry_messages_counts')->where('user_id' , $to)->first();
                if(blank($likedCount))
                {
                    DB::table('ry_messages_counts')->insert(array(
                        'user_id'=>$to,
                        'liked'=>1,
                        'created_at'=>$this->now,
                        'updated_at'=>$this->now,
                    ));
                }else{
                    DB::table('ry_messages_counts')->where('user_id' , $to)->increment('liked' , 1 , array(
                        'updated_at'=>$this->now,
                    ));
                }
                MoreTimeUserScoreUpdate::dispatch($to , 'likedVideo' , $likeId)->onQueue('helloo_{more_time_user_score_update}');
            }
        }
        DB::table('ry_like_messages')->insert(array(
            'messageId'=>$messageId,
            'from_id'=>$from,
            'to_id'=>$to,
            'liked_id'=>$likeId,
            'created_at'=>$this->now
        ));

    }

}
