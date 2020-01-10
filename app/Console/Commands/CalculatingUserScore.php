<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Post;
use App\Models\Like;
use App\Models\Dislike;
use App\Models\PostComment;
use Illuminate\Console\Command;

class CalculatingUserScore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculating:reset_user_score';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'calculating reset user score';

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
        User::chunk(10, function ($users){
            $userIds = $users->pluck('user_id')->all();
            $posts = Post::whereIn('user_id' , $userIds)->where('post_created_at' , '>=' , '2019-11-25 14:36:00')->groupBy('user_id')->select('user_id', \DB::raw('count(*) as num'))->get();
            $comments = PostComment::whereIn('user_id' , $userIds)->where('comment_created_at' , '>=' , '2019-11-25 14:36:00')->groupBy('user_id')->select('user_id', \DB::raw('count(*) as num'))->get();
            $likes = Like::whereIn('user_id' , $userIds)->where('created_at' , '>=' , '2019-11-25 14:36:00')->groupBy('user_id')->select('user_id', \DB::raw('count(*) as num'))->get();
            $dislikes = Dislike::whereIn('user_id' , $userIds)->where('created_at' , '>=' , '2019-11-25 14:36:00')->groupBy('user_id')->select('user_id', \DB::raw('count(*) as num'))->get();
            foreach ($users as $user) {
                $post = intval(optional($posts->where('user_id' , $user->user_id)->first())->num);
                $comment = intval(optional($comments->where('user_id' , $user->user_id)->first())->num);
                $like = intval(optional($likes->where('user_id' , $user->user_id)->first())->num);
                $dislike = intval(optional($dislikes->where('user_id' , $user->user_id)->first())->num);
                $score = $post*2;
                $score = $score+$comment*3;
                $score = $score+$like;
                $score = $score+$dislike;
                $user->calculatingScore($score);
            }
        });
    }
}
