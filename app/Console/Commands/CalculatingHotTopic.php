<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Topic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class CalculatingHotTopic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculating:hot_topic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'calculating hot topic';

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
     * @return void
     */
    public function handle()
    {
        $now = Carbon::now();
        $today = Carbon::today();
        $startTime = $today->subDays(100)->timestamp;
        $endTime = $now->timestamp;
        $topic = Topic::where('topic_created_at' , '<=' , $endTime)
            ->where('topic_created_at' , '>=' , $startTime)->select('topic_content', DB::raw('COUNT(id) as num'))->groupBy('topic_content')->orderBy('num' , "DESC")->limit(10)->get()->map(function($item , $index){
                return $item->topic_content;
            })->toArray();
        $key = "hot_topic_auto";
        Redis::set($key , \json_encode($topic));
        Redis::expirt($key , 86400);
    }
}
