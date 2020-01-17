<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\Like;
use App\Models\Dislike;
use App\Models\PostComment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class CalculatingPostCountry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculating:post_country';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'calculating post country';

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
        Post::chunk(10 , function($posts){
            foreach ($posts as $post)
            {
                $countryData = collect();
                $likes = Like::select(\DB::raw('likable_id as post_id ,likable_country as country_id , count(likable_country) AS num'))->where('likable_id' , $post->post_id)->where('likable_type' , Post::class)->groupBy('likable_country')->get();
                $dislikes = Dislike::select(\DB::raw('dislikable_id as post_id,dislikable_country as country_id , count(dislikable_country) AS num'))->where('dislikable_id' , $post->post_id)->groupBy('dislikable_country')->get();
                $comments = PostComment::select('post_id' , 'comment_country_id as country_id' , \DB::raw('count(comment_country_id) AS num'))->where('post_id' , $post->post_id)->groupBy('comment_country_id')->get();
                $countryIds = $likes->pluck('country_id')->merge($dislikes->pluck('country_id'))->merge($comments->pluck('country_id'))->unique()->values();
                $countryIds->map(function($item , $key) use ($likes , $dislikes , $comments ,&$countryData){
                    $num = intval(optional($likes->where('country_id' , $item)->first())->num);
                    $num = $num+intval(optional($dislikes->where('country_id' , $item)->first())->num);
                    $num = $num+intval(optional($comments->where('country_id' , $item)->first())->num);
                    $countryData->put($item , $num);
                });
                if($countryData->isNotEmpty())
                {
                    $postKey = 'post.'.$post->post_id.'.data';
                    $field = 'country';
                    Redis::hset($postKey , $field , $countryData);
                }
            }
        });
    }
}
