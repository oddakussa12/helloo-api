<?php

namespace App\Custom\PushServer\Fcm;

use Illuminate\Support\Facades\Log;
use LaravelFCM\Facades\FCM;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use LaravelFCM\Message\Topics;
use LaravelFCM\Sender\FCMGroup;

class Push
{
    private $title;
    private $desc;
    private $token;
    private $sound;
    private $extras;

    public function __construct($params)
    {
        $this->title = array_get($params, 'title', '这是一条mipush推送消息');
        $this->desc  = array_get($params, 'title', '这是一条mipush推送消息');
        $this->token = array_get($params, 'registrationId');
        $this->sound = array_get($params, 'sound', 'default');
        $this->extras= array_get($params, 'extras', []);
    }


    public function send()
    {

        (new FCMGroup())->createGroup('en', is_array($this->token) ? $this->token : [$this->token]);

        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60*20);

        $notificationBuilder = new PayloadNotificationBuilder($this->title);
        $notificationBuilder->setBody($this->desc)
            ->setSound($this->sound);

        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData($this->extras);

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();

//        $token = "a_registration_from_your_database";
//        // You must change it to get your tokens
//        $tokens = MYDATABASE::pluck('fcm_token')->toArray();

        $downstreamResponse = FCM::sendTo($this->token, $option, $notification, $data);

        $downstreamResponse->numberSuccess();
        $downstreamResponse->numberFailure();
        $downstreamResponse->numberModification();

        // return Array - you must remove all this tokens in your database
        $downstreamResponse->tokensToDelete();

        // return Array (key : oldToken, value : new token - you must change the token in your database)
        $downstreamResponse->tokensToModify();

        // return Array - you should try to resend the message to the tokens in the array
        $downstreamResponse->tokensToRetry();

        // return Array (key:token, value:error) - in production you should remove from your database the tokens
        //$downstreamResponse->tokensWithError();

        $status = [
            'success'=> $downstreamResponse->numberSuccess(),
            'fail'   => $downstreamResponse->numberFailure(),
            'msg'    => $downstreamResponse->tokensWithError()
        ];
        Log::info('FCM PUSH STATUS:', $status);
        //Log::info('FCM PUSH STATUS:', [serialize($downstreamResponse)]);

        return $status;
    }

    public function sendTopic()
    {
        $notificationBuilder = new PayloadNotificationBuilder($this->title);
        $notificationBuilder->setBody($this->desc)
            ->setSound($this->sound);

        $notification = $notificationBuilder->build();

        $topic = new Topics();

        // 单个topic
        //$topic->topic('news');

        //多个topic
        $topic = new Topics();
        $topic->topic('news')->andTopic(function($condition) {
            $condition->topic('economic')->orTopic('cultural');
        });

        $topicResponse = FCM::sendToTopic($topic, null, $notification, null);

        $topicResponse->isSuccess();
        $topicResponse->shouldRetry();
        $topicResponse->error();

        return [
            'success'    => $topicResponse->isSuccess(),
            'shouldRetry'=> $topicResponse->shouldRetry(),
            'error'      => $topicResponse->error(),
        ];
    }

}
