<?php

namespace App\Console\Commands;



use App\Models\UserScore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class RankInit extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rank:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rank init';

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
