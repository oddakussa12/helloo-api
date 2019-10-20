<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculatingRete extends Command
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
    protected $description = 'Calculating post rate';

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
        //
        Log::info('-----------定时积分计算开始-----------');
        Post::withTrashed()->withCount('comments')->chunk(10, function ($posts){
            $i = 1;
            foreach ($posts as $post) {
                Log::info("-----------第{$i}次任务开始-----------");
                $post->calculatingRate();
                Log::info("-----------第{$i}次任务结束-----------");
                $i++;
            }
        });
        Log::info('-----------定时积分计算结束-----------');
    }
}
