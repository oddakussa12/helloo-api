<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Repositories\Contracts\UserRepository;

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
                    'Helloo:VoiceMsg',
                    'Helloo:GoodsMsg'
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
            $fromId = $raw['fromUserId'];
            $toId = $raw['toUserId'];
            $from = app(UserRepository::class)->findByUserId($fromId);
            $to = app(UserRepository::class)->findByUserId($toId);
            $data = array(
                'chat_msg_uid'  => $raw['msgUID'],
                'chat_from_id'  => $raw['fromUserId'],
                'chat_from_type'  => $from->get('user_shop' , 0),
                'chat_to_id'    => $raw['toUserId'],
                'chat_to_type'  => $to->get('user_shop' , 0),
                'chat_msg_type' => $raw['objectName'],
                'chat_channel' => $raw['channelType'],
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
            if(isset($content['goodsId'])) { //goods message
                $messageContent['message_content'] = $content['goodsId'];
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
            if(isset($content['user']))
            {
                $u = $content['user'];
                if(isset($u['extra']))
                {
                    $extra = \json_decode($u['extra'] , true);
                    if(is_array($extra)&&isset($extra['referrer']))
                    {
                        $data['chat_referrer'] = strval($extra['referrer']);
                    }
                }
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
                $videoUrl = $content['videoUrl'] ?? '';
                $isRecord = isset($extra['isRecord'])?intval($extra['isRecord']):0;
                $voiceName = $extra['changeVoiceName'] ?? '';
                $bundleName = isset($content['bundleName'])&&$content['bundleName']!='none'?$content['bundleName']:'';
                $this->handleVideo($raw['fromUserId'] , $raw['toUserId'] , $messageId , $videoUrl , $isRecord , $voiceName , $bundleName);
            }

            if($messageContent['message_type']=='Helloo:VoiceMsg')
            {
                $audioUrl = $content['uri'] ?? '';
                $duration = $content['duration'] ?? 0;
                $this->handleAudio($raw['fromUserId'] , $raw['toUserId'] , $raw['msgUID'] , $audioUrl , $duration);
            }

            if($messageContent['message_type']=='RC:ImgMsg')
            {
                $this->handleImage($raw['fromUserId'] , $raw['toUserId']);
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
            $flag = DB::table('ry_chats_logs')->where('from_id' , $from)->where('to_id' , $to)->where('type' , 'txt')->first();
            if(blank($flag))
            {
                DB::table('ry_chats_logs')->insert(array(
                    'from_id'=>$from,
                    'to_id'=>$to,
                    'type'=>'txt',
                    'created_at'=>$this->now,
                ));
                $counts = DB::table('users_kpi_counts')->where('user_id' , $from)->first();
                if(blank($counts))
                {
                    $id = DB::table('users_kpi_counts')->insertGetId(array(
                        'user_id'=>$from,
                        'txt'=>1,
                        'created_at'=>$this->now,
                        'updated_at'=>$this->now,
                    ));
                }else{
                    $id = $counts->id;
                    DB::table('users_kpi_counts')->where('id' , $id)->increment('txt' , 1 , array(
                        'updated_at'=>$this->now,
                    ));
                }
//                MoreTimeUserScoreUpdate::dispatch($from , 'firstTxtMessage' , $id)->onQueue('helloo_{more_time_user_score_update}');
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
//                    MoreTimeUserScoreUpdate::dispatch($from , 'tenVideoMessage')->onQueue('helloo_{more_time_user_score_update}');
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
//                    MoreTimeUserScoreUpdate::dispatch($to , 'tenVideoMessage')->onQueue('helloo_{more_time_user_score_update}');
                }
            }
        }
        Redis::expireAt($hashKey , Carbon::createFromFormat('Y-m-d' , $this->day)->endOfDay()->addMinutes(15)->timestamp);
        Redis::expireAt($setKey , Carbon::createFromFormat('Y-m-d' , $this->day)->addDays(7)->endOfDay()->timestamp);
        $counts = DB::table('users_kpi_counts')->where('user_id' , $from)->first();
        if(blank($counts))
        {
            DB::table('users_kpi_counts')->insertGetId(array(
                'user_id'=>$from,
                'sent'=>1,
                'created_at'=>$this->now,
                'updated_at'=>$this->now,
            ));
        }else{
            $id = $counts->id;
            DB::table('users_kpi_counts')->where('id' , $id)->increment('sent' , 1 , array(
                'updated_at'=>$this->now,
            ));
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
            $flag = DB::table('ry_chats_logs')->where('from_id' , $from)->where('to_id' , $to)->where('type' , 'video')->first();
            if(blank($flag))
            {
                DB::table('ry_chats_logs')->insert(array(
                    'from_id'=>$from,
                    'to_id'=>$to,
                    'type'=>'video',
                    'created_at'=>$this->now,
                ));
                $counts = DB::table('users_kpi_counts')->where('user_id' , $from)->first();
                if(blank($counts))
                {
                    $id = DB::table('users_kpi_counts')->insertGetId((array(
                        'user_id'=>$from,
                        'video'=>1,
                        'created_at'=>$this->now,
                        'updated_at'=>$this->now,
                    )));
                }else{
                    $id = $counts->id;
                    DB::table('users_kpi_counts')->where('id' , $id)->increment('video' , 1 , array(
                        'updated_at'=>$this->now,
                    ));
                }
//                MoreTimeUserScoreUpdate::dispatch($from , 'firstVideoMessage' , $id)->onQueue('helloo_{more_time_user_score_update}');
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
//                    MoreTimeUserScoreUpdate::dispatch($from , 'tenVideoMessage' , $to)->onQueue('helloo_{more_time_user_score_update}');
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
//                    MoreTimeUserScoreUpdate::dispatch($to , 'tenVideoMessage' , $from)->onQueue('helloo_{more_time_user_score_update}');
                }
            }
        }
        Redis::expireAt($hashKey , Carbon::createFromFormat('Y-m-d' , $this->day)->endOfDay()->addMinutes(15)->timestamp);
        Redis::expireAt($setKey , Carbon::createFromFormat('Y-m-d' , $this->day)->addDays(7)->endOfDay()->timestamp);
        if(!blank($bundleName))
        {
            $props = DB::table('users_props')->where('user_id' , $from)->first();
            if(blank($props))
            {
                $data = array($bundleName=>1);
                DB::table('users_props')->insert(array(
                    'user_id'=>$from,
                    'props'=>\json_encode($data),
                    'created_at'=>$this->now,
                    'updated_at'=>$this->now,
                ));
            }else{
                $data = \json_decode($props->props , true);
                if(is_array($data))
                {
                    if(isset($data[$bundleName]))
                    {
                        $data[$bundleName] = $data[$bundleName]+1;
                    }else{
                        $data[$bundleName] = 1;
                    }
                }else{
                    $data[$bundleName] = 1;
                }
                DB::table('users_props')->where('id' , $props->id)->update(array(
                    'props'=>\json_encode($data),
                    'updated_at'=>$this->now
                ));
            }
            $counts = DB::table('users_kpi_counts')->where('user_id' , $from)->first();
            if(blank($counts))
            {
                DB::table('users_kpi_counts')->insertGetId((array(
                    'user_id'=>$from,
                    'props'=>1,
                    'created_at'=>$this->now,
                    'updated_at'=>$this->now,
                )));
                $count = 1;
            }else{
                $id = $counts->id;
                $count = count($data);
                DB::table('users_kpi_counts')->where('id' , $id)->update(array(
                    'props'=>$count,
                    'updated_at'=>$this->now,
                ));
            }
//            if($count==5&&$counts->props!=5)
//            {
//                OneTimeUserScoreUpdate::dispatch($from , 'fiveMaskVideo')->onQueue('helloo_{one_time_user_score_update}');
//            }
//            if($count==50&&$counts->props!=50)
//            {
//                GreatUserScoreUpdate::dispatch($from , 'maskCollection')->onQueue('helloo_{great_user_score_update}');
//            }
        }
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
        $counts = DB::table('users_kpi_counts')->where('user_id' , $from)->first();
        if(blank($counts))
        {
            DB::table('users_kpi_counts')->insertGetId(array(
                'user_id'=>$from,
                'sent'=>1,
                'created_at'=>$this->now,
                'updated_at'=>$this->now,
            ));
        }else{
            $id = $counts->id;
            DB::table('users_kpi_counts')->where('id' , $id)->increment('sent' , 1 , array(
                'updated_at'=>$this->now,
            ));
        }
    }

    private function handleAudio($from , $to , $messageId , $audioUrl , $duration )
    {
        $lock_key = "helloo:message:service:unique-audio-".strval($from).'-'.strval($to);
        if(!Redis::exists($lock_key))
        {
            Redis::set($lock_key , 1);
            Redis::expire($lock_key , 600);
            $flag = DB::table('ry_chats_logs')->where('from_id' , $from)->where('to_id' , $to)->where('type' , 'audio')->first();
            if(blank($flag))
            {
                DB::table('ry_chats_logs')->insert(array(
                    'from_id'=>$from,
                    'to_id'=>$to,
                    'type'=>'audio',
                    'created_at'=>$this->now,
                ));
                $counts = DB::table('users_kpi_counts')->where('user_id' , $from)->first();
                if(blank($counts))
                {
                    DB::table('users_kpi_counts')->insertGetId((array(
                        'user_id'=>$from,
                        'audio'=>1,
                        'created_at'=>$this->now,
                        'updated_at'=>$this->now,
                    )));
                }else{
                    $id = $counts->id;
                    DB::table('users_kpi_counts')->where('id' , $id)->increment('audio' , 1 , array(
                        'updated_at'=>$this->now,
                    ));
                }
            }
        }
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
        $counts = DB::table('users_kpi_counts')->where('user_id' , $from)->first();
        if(blank($counts))
        {
            DB::table('users_kpi_counts')->insertGetId(array(
                'user_id'=>$from,
                'sent'=>1,
                'created_at'=>$this->now,
                'updated_at'=>$this->now,
            ));
        }else{
            $id = $counts->id;
            DB::table('users_kpi_counts')->where('id' , $id)->increment('sent' , 1 , array(
                'updated_at'=>$this->now,
            ));
        }
    }

    private function handleImage($from , $to)
    {
        $lock_key = "helloo:message:service:unique-image-".strval($from).'-'.strval($to);
        if(!Redis::exists($lock_key))
        {
            Redis::set($lock_key , 1);
            Redis::expire($lock_key , 600);
            $flag = DB::table('ry_chats_logs')->where('from_id' , $from)->where('to_id' , $to)->where('type' , 'image')->first();
            if(blank($flag))
            {
                DB::table('ry_chats_logs')->insert(array(
                    'from_id'=>$from,
                    'to_id'=>$to,
                    'type'=>'image',
                    'created_at'=>$this->now,
                ));
                $counts = DB::table('users_kpi_counts')->where('user_id' , $from)->first();
                if(blank($counts))
                {
                    DB::table('users_kpi_counts')->insertGetId((array(
                        'user_id'=>$from,
                        'image'=>1,
                        'created_at'=>$this->now,
                        'updated_at'=>$this->now,
                    )));
                }else{
                    $id = $counts->id;
                    DB::table('users_kpi_counts')->where('id' , $id)->increment('image' , 1 , array(
                        'updated_at'=>$this->now,
                    ));
                }
            }
        }
        $counts = DB::table('users_kpi_counts')->where('user_id' , $from)->first();
        if(blank($counts))
        {
            DB::table('users_kpi_counts')->insertGetId(array(
                'user_id'=>$from,
                'sent'=>1,
                'created_at'=>$this->now,
                'updated_at'=>$this->now,
            ));
        }else{
            $id = $counts->id;
            DB::table('users_kpi_counts')->where('id' , $id)->increment('sent' , 1 , array(
                'updated_at'=>$this->now,
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
                $likeCount = DB::table('users_kpi_counts')->where('user_id' , $from)->first();
                if(blank($likeCount))
                {
                    DB::table('users_kpi_counts')->insert(array(
                        'user_id'=>$from,
                        'like'=>1,
                        'created_at'=>$this->now,
                        'updated_at'=>$this->now,
                    ));
                }else{
                    DB::table('users_kpi_counts')->where('user_id' , $from)->increment('like' , 1 , array(
                        'updated_at'=>$this->now,
                    ));
                }
//                MoreTimeUserScoreUpdate::dispatch($from , 'likeVideo' , $likeId)->onQueue('helloo_{more_time_user_score_update}');
                $likedCount = DB::table('users_kpi_counts')->where('user_id' , $to)->first();
                if(blank($likedCount))
                {
                    DB::table('users_kpi_counts')->insert(array(
                        'user_id'=>$to,
                        'liked'=>1,
                        'created_at'=>$this->now,
                        'updated_at'=>$this->now,
                    ));
                }else{
                    DB::table('users_kpi_counts')->where('user_id' , $to)->increment('liked' , 1 , array(
                        'updated_at'=>$this->now,
                    ));
                }
//                MoreTimeUserScoreUpdate::dispatch($to , 'likedVideo' , $likeId)->onQueue('helloo_{more_time_user_score_update}');
            }
        }
        DB::table('ry_like_messages')->insert(array(
            'message_id'=>$messageId,
            'from_id'=>$from,
            'to_id'=>$to,
            'liked_id'=>$likeId,
            'created_at'=>$this->now
        ));

    }

}
