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
                'raw'    => \json_encode($raw , JSON_UNESCAPED_UNICODE),
                'errors' => \json_encode($validator->errors() , JSON_UNESCAPED_UNICODE)
            );
            RyChatFailed::create($data);
        } else {
            $messageContent['message_id'] = $raw['msgUID'];
            $messageContent['message_time'] = $raw['msgTimestamp'];
            $messageContent['message_type'] = $raw['objectName'];
            $data = array(
                'chat_msg_uid'  => $raw['msgUID'],
                'chat_from_id'  => $raw['fromUserId'],
                'chat_to_id'    => $raw['toUserId'],
                'chat_msg_type' => $raw['objectName'],
                'chat_time'     => $raw['msgTimestamp'],

//                'chat_channel_type'=>$raw['channelType'],
//                'chat_sensitive_type'=>$raw['sensitiveType']
            );
            if(isset($raw['source'])) {
                $data['chat_source'] = $raw['source'];
            }
//            if(isset($raw['groupUserIds']))
//            {
//                $data['chat_group_to_id'] = $raw['groupUserIds'];
//            }
            if(isset($raw['content']))
            {
                $content = \json_decode($raw['content'] , true);

                $data['chat_default'] = $raw ['objectName'] == 'RC:TxtMsg' ? $this->chatContentType($content['content']) : 0;

                if(isset($content['content']))
                {
                    $messageContent['message_content'] = $content['content'];
                }
                if(isset($content['imageUri']))
                {
                    $messageContent['message_content'] = $content['imageUri'];
                }
                if(isset($content['user'])) {
                    $data['chat_from_name'] = $content['user']['name'];
                }
//                if(isset($content['user']['extra']))
//                {
//                    $data['chat_from_extra'] = \json_encode($content['user']['extra'] , JSON_UNESCAPED_UNICODE);
//                }
                try{
                    RyMessage::create(
                        $messageContent
                    );
                }catch (\Exception $e)
                {
                    \Log::error('insert ry message fail,reason:'.\json_encode($e->getMessage()));
                }

            }
            $ryChat = RyChats::create($data);

//            RyChatRaw::create(array('chat_id'=>$ryChat->chat_id , 'raw'=>\json_encode($raw , JSON_UNESCAPED_UNICODE),'chat_time'=>$raw['msgTimestamp']));

        }

    }

    public function chatContentType($content)
    {
        $list = [
            "你好，很高兴认识你！😉",
            "这是我来Yooul的第一天！来教教我怎么用吧～😝",
            "Hi！你的头像真好看😊",
            "嗨，您好！ 你今天過得怎麼樣？😉",
            "你好！ 我是新用戶。 想聊天嗎？😝",
            "嘿! 你的照片看起來很可愛😊",
            "Hi there! How's your day going?😉",
            "Hello! I am a new user there. Wanna chat?😝",
            "Hey! Your picture looks cute😊",
            "Hallo! Wie läuft dein Tag?😉",
            "Hallo! Ich bin dort ein neuer Benutzer. Willst du chatten? 😝",
            "Hallo! Dein Bild sieht süß aus😊",
            "¡Hola! ¿Cómo va tu día? 😉",
            "¡Hola! Soy un nuevo usuario allí. ¿Quieres chatear? 😝",
            "¡Oye! Tu foto se ve linda😊",
            "Salut! Comment se passe ta journée? 😉",
            "salut! Je suis un nouvel utilisateur là-bas. Tu veux discuter? 😝",
            "Hey! Ta photo est mignonne😊",
            "नमस्ते! आपका दिन कैसा रहा??😉",
            "नमस्कार! मैं वहां एक नया उपयोगकर्ता हूं। चैट करना चाहते हैं? 😝",
            "अरे! आपकी तस्वीर क्यूट लगती है😊",
            "Hai yang disana! Bagaimana harimu? 😉",
            "Halo! Saya pengguna baru di sana. Ingin mengobrol? 😝",
            "Hei! Gambar Anda terlihat lucu😊",
            "こんにちは！ 今日はどうですか？😉",
            "こんにちは！ 私はそこで新しいユーザーです。 チャットしたいですか？😝 ",
            "おい！ あなたの写真はかわいいですね😊",
            "안녕! 오늘 하루는 어때? 😉",
            "여보세요! 나는 새로운 사용자입니다. 채팅을 원하십니까? 😝",
            "야! 사진이 귀엽다😊",
            "Всем привет! Как проходит твой день?😉",
            "Здравствуйте! Я новый пользователь там. Хотите поговорить? 😝",
            "Привет! Твоя картинка выглядит мило😊",
            "สวัสดี! วันนี้เป็นอย่างไรบ้าง😉",
            "สวัสดี! ฉันเป็นผู้ใช้ใหม่ที่นั่น ต้องการแชทไหม😝",
            "เฮ้! รูปของคุณดูน่ารัก😊",
            "Chào bạn Ngày hôm nay của bạn thế nào?😉",
            "Xin chào! Tôi là một người dùng mới ở đó. Muốn trò chuyện không?😝",
            "Chào! Hình ảnh của bạn trông thật dễ thương😊"
        ];

        return in_array($content, $list) ? 1 : 0;

    }
}
