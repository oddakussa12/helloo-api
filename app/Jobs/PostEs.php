<?php

namespace App\Jobs;

use App\Models\Es;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PostEs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $post = $this->post;
        $post->create_at  = optional($post->post_created_at)->toDateTimeString();
        $post->post_media = (!empty($post->post_type) && !empty($post->post_media)) ? postMedia($post->post_type, $post->post_media) : null;

        $result           = $post->getAttributes();
        $postInfo         = $post->getTranslationsArray();

        unset($result['post_country_id'], $result['post_rate'], $result['post_event_country_id'], $result['post_created_at']);

        $postList = array_map(function($v) use ($result){unset($v['post_title']);return array_merge($result, $v);}, $postInfo);
        $postList = array_column($postList, null);

        $data     = (new Es(config('scout.elasticsearch.post')))->batchCreate($postList);
        if ($data==null) {
            $data = (new Es(config('scout.elasticsearch.post')))->batchCreate($postList);
        }
    }
}
