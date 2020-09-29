<?php

namespace App\Console\Commands;

use App\Traits\CachableUser;
use App\Jobs\Test as TestJob;
use Illuminate\Console\Command;



class Test extends Command
{
    use CachableUser;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto test';

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
        $oneMonthAgo = $now->subDays(3)->format('Y-m-d 00:00:00');
        $newKey = config('redis-key.post.post_index_new');
        $perPage = 10;
        $count = Redis::zcard($newKey);
        $redis = new RedisList();
        $lastPage = ceil($count/$perPage);
        for ($page=1;$page<=$lastPage;$page++) {
            $offset = ($page - 1) * $perPage;
            $posts = $redis->zRevRangeByScore($newKey, '+inf', strtotime($oneMonthAgo), true, array($offset, $perPage));
            if (empty($posts)) {
                break;
            }
            foreach ($posts as $postId=>$time)
            {
                $postKey = 'post.'.$postId.'.data';
                $after = $this->likeCount($postId);
                $coefficient = intval(Redis::get('fake_like_coefficient'));
                Redis::hmset($postKey , array('temp_like'=>fakeLike($after['like'] , $coefficient)));
            }
        }
    }

}
