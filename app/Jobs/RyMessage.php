<?php

namespace App\Jobs;

use Aws\DoctrineCacheAdapter;
use Illuminate\Bus\Queueable;
use Aws\Kinesis\KinesisClient;
use Doctrine\Common\Cache\ApcuCache;
use Illuminate\Queue\SerializesModels;
use Aws\Credentials\CredentialProvider;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RyMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $config;

    private $message;

    public function __construct($message)
    {
        $this->message = $message;
        $this->config = config('aws.Kinesis');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $config = $this->config;
        $cache = new DoctrineCacheAdapter(new ApcuCache);
        $provider = CredentialProvider::defaultProvider();
        $cachedProvider = CredentialProvider::cache($provider, $cache);
        $config['credentials'] = $cachedProvider;
        $kinesisClient = new KinesisClient($config);

        $name = "Yooul_Ry_Message";
        $message = $this->getMessage();
        $content = \json_encode($message , JSON_UNESCAPED_UNICODE).PHP_EOL;
        $groupID = $message['chat_msg_uid'];

        try {
            $result = $kinesisClient->PutRecord([
                'Data' => $content,
                'StreamName' => $name,
                'PartitionKey' => $groupID
            ]);
        } catch (\Aws\Exception\AwsException $e) {
            \Log::error(\json_encode($e->getMessage() , JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        }
    }

    public function getMessage()
    {
        $message = $this->message;

        $data = array(
            'chat_msg_uid'  => $message['msgUID'],
            'chat_from_id'  => $message['fromUserId'],
            'chat_to_id'    => $message['toUserId'],
            'chat_source'   => $message['source'],
            'chat_msg_type' => $message['objectName'],
            'chat_time'     => $message['msgTimestamp'],
            'chat_channel_type'=>$message['channelType'],
            'chat_sensitive_type'=>$message['sensitiveType'],
        );
        if(isset($message['content']))
        {
            $content = \json_decode($message['content'] , true);
            $data['chat_content'] = isset($content['content'])?$content['content']:'';
            $data['chat_image'] = isset($content['imageUri'])?$content['imageUri']:'';
            $data['chat_from_name'] = isset($content['user']['name'])?$content['user']['name']:'';
            $data['chat_from_extra'] = isset($content['user']['extra'])?\json_encode($content['user']['extra'] , JSON_UNESCAPED_UNICODE):'';
        }
        return $data;
    }
}
