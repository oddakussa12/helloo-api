<?php

namespace App\Jobs;

use App\Models\Es;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class TopicEs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $post;

    public $topics;

    public function __construct(Post $post , $topics)
    {
        $this->post = $post;
        $this->topics = $topics;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $post = $this->post;
        $topics = $this->topics;
        $userId = $post->user_id;
        $postId = $post->getKey();
        $time = time();
        $topicData = array_map(function($v) use ($userId , $postId , $time){
            return array(
                'user_id'=>$userId,
                'post_id'=>$postId,
                'topic_content'=>$v,
                'topic_created_at'=>$time,
                'topic_updated_at'=>$time
            );
        } , $topics);
        \DB::table('topics')->insert($topicData);
        $data     = (new Es(config('scout.elasticsearch.topic')))->batchCreate($topicData);
        if ($data==null) {
            $data = (new Es(config('scout.elasticsearch.topic')))->batchCreate($topicData);
        }
    }
}
