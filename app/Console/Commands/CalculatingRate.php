<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Post;
use Illuminate\Console\Command;

class CalculatingRate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculating:rate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'calculating post rate';

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
        $now = Carbon::now();
        $today = Carbon::today();
        $startTime = $today->subDays(15)->toDateTimeString();
        $endTime = $now->toDateTimeString();
        Post::withTrashed()
            ->where('post_created_at' , '<=' , $endTime)
            ->where('post_created_at' , '>=' , $startTime)->chunk(10, function ($posts){
            foreach ($posts as $post) {
                $post->calculatingRate();
            }
        });
    }
}
