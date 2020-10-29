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

    public $type;

    public function __construct(Post $post , $type='create')
    {
        $this->post = $post;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $post = $this->post;
        if ($this->type == 'create') {
            $post->create_at = optional($post->post_created_at)->toDateTimeString();
            $post->post_media = (!empty($post->post_type) && !empty($post->post_media)) ? postMedia($post->post_type, $post->post_media) : null;

            $result = $post->getAttributes();
            $postInfo = $post->getTranslationsArray();
            $result = collect($result)->only(
                array(
                    'post_id',
                    'post_uuid',
                    'user_id',
                    'post_category_id',
                    'post_media',
                    'post_content_default_locale',
                    'post_type',
                    'show_type',
                    'post_event_country_id',
                    'post_created_at',
                )
            )->all();
            $result['create_at'] = $result['post_created_at'];
            unset($result['post_created_at']);
            $postList = array_map(function ($v) use ($result) {
                unset($v['post_title']);
                return array_merge($result, $v);
            }, $postInfo);
            $postList = array_column($postList, null);
            $data = (new Es(config('scout.elasticsearch.post')))->batchCreate($postList);
            if ($data == null) {
                $data = (new Es(config('scout.elasticsearch.post')))->batchCreate($postList);
            }
        }else{
            (new Es(config('scout.elasticsearch.post'), ['updateField'=>['post_is_delete'=>1]]))->update($post->post_id, 'post_id');
        }
    }
}
