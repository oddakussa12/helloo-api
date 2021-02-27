<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class FriendScore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $userId;
    private $score;
    private $game;

    public function __construct($userId , $score , $game)
    {
        $this->userId = $userId;
        $this->score = $score;
        $this->game = $game;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::table('users_friends')
            ->where('user_id' , $this->userId)
            ->orderByDesc('created_at')
            ->chunk(100, function($friends){
                foreach ($friends as $friend)
                {
                    $sortKey = "helloo:account:friend:game:rank:sort:".$friend->friend_id.'-'.$this->game;
                    if($friend->friend_id!=$this->userId&&Redis::exists($sortKey))
                    {
                        Redis::zadd($sortKey , $this->score , $this->userId);
                    }
                }
            });
    }

}
