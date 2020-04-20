<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class CalculatingRateLimit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculating:rate_limit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'calculating post rate limit';

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
//        $lastPost = Post::withTrashed()->order('post_id' , 'DESC')->limit(2000)->first();
//        Post::withTrashed()->chunk(10, function ($posts){
//            foreach ($posts as $post) {
//                $post->calculatingRate();
//            }
//        });
    }
}
