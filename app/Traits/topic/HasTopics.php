<?php
namespace App\Traits\topic;

use Illuminate\Support\Facades\Redis;

trait HasTopics
{
    public function attachTopics(array $topics)
    {
        if(!blank($topics))
        {
            $this->userTopics = $topics;
            $topicRateKey = config('redis-key.topic.topic_index_rate');
            $topicNewKey = config('redis-key.topic.topic_index_new');
            $now = time();
            $userId = $this->user_id;
            $postId = $this->getKey();
            $firstRate = first_rate_comment_v2();
            Redis::pipeline(function ($pipe) use ($topics , $topicRateKey , $topicNewKey , $now , $userId , $postId , $firstRate){
                array_walk($topics , function($item , $index) use ($pipe , $topicRateKey , $topicNewKey , $now , $userId , $postId , $firstRate){
                    $key = strval($item);
                    $pipe->zincrby($topicRateKey , 1 , $key);
                    $pipe->zadd($topicNewKey , $now , $key);
                    $pipe->zadd($key."_new" , $now , $postId);
                    $pipe->zadd($key."_rate" , $firstRate , $postId);
                    $userTopicKey = 'user.'.$userId.'.topics';
                    $pipe->zadd($userTopicKey , $now , $key);
                });
                $postKey = 'post.'.$postId.'.data';
                $pipe->hmset($postKey , array(
                    "topics" => \json_encode($topics)
                ));
            });
        }
        return $this;
    }
}