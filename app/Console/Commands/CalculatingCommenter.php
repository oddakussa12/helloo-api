<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class CalculatingCommenter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculating:commenter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'calculating post commenter';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $field = 'commenter';
        Post::chunk(10 , function($posts) use ($field){
            $postIds = $posts->pluck('post_id')->all();
            $sql = "select count(`user_id`) as `num`,`user_id`, `post_id` from `f_posts_comments` where `post_id` in (
".join(',' , $postIds)."
) AND `comment_deleted_at is NUll` GROUP BY `post_id`,`user_id`;";
            $commenters = collect(\DB::select($sql));
            foreach ($postIds as $postId)
            {
                $postKey = 'post.'.$postId.'.data';
                $commenter = $commenters->where('post_id' , $postId)->values();
                $commentData = $commenter->pluck('num' , 'user_id');
                if($commenters->isNotEmpty())
                {
                    Redis::hset($postKey , $field , collect($commentData));
                }
            }
        });
    }
}
