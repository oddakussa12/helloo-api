<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserFriend;
use App\Models\UserFriendRequest;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class Rank implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $school;

    public function __construct($school)
    {
        $this->school = $school;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $rank    = [2=>1548065282, 3=>1234072139, 6=>1562134513, 7=>1402551869, 11=>2091996857, 23=>1885497935, 35=>1399005307];
        $memKey  = 'helloo:account:user-score-rank';
        $members = Redis::zrevrangebyscore($memKey, '+inf', '-inf', ['withScores'=>true, 'limit'=>[0,100]]);

        foreach ($rank as $kk=>$vv) {
            Redis::zrem($memKey, $vv);
        }
        foreach ($rank as $kk=>$vv) {
            $min   = current(array_slice($members, $kk-1, 1));
            $max   = current(array_slice($members, $kk-2, 1));
            $score = mt_rand($min, $max);
            // $score    = intval(array_sum(array_slice($members, $kk-2, 2))/2);
            Redis::zadd($memKey, $score, $vv);
        }
    }

}
