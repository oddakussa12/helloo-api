<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class GameScore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $userId;
    private $snowId;
    private $score;
    private $game;
    private $now;

    public function __construct($userId , $score , $snowId , $game , $now)
    {
        $this->userId = $userId;
        $this->snowId = $snowId;
        $this->score = $score;
        $this->game = $game;
        $this->now = Carbon::createFromTimestamp($now)->toDateTimeString();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $counts = DB::table('users_kpi_counts')->where('user_id' , $this->userId)->first();
        $score = $this->score;
        $userId = $this->userId;
        $snowId = $this->snowId;
        if(blank($counts))
        {
            DB::table('users_kpi_counts')->insertGetId(array(
                'user_id'=>$this->userId,
                'game_score'=>$this->score,
                'created_at'=>$this->now,
                'updated_at'=>$this->now,
            ));
//            if($score>=1500)
//            {
//                GreatUserScoreUpdate::dispatch($userId , 'gameIII' , $snowId)->onQueue('helloo_{great_user_score_update}');
//            }elseif ($score<1500&&$score>=800)
//            {
//                GreatUserScoreUpdate::dispatch($userId , 'gameII' , $snowId)->onQueue('helloo_{great_user_score_update}');
//            }elseif ($score<800&&$score>=300)
//            {
//                GreatUserScoreUpdate::dispatch($userId , 'gameI' , $snowId)->onQueue('helloo_{great_user_score_update}');
//            }
        }else{
            $id = $counts->id;
            if($score>$counts->game_score)
            {
                DB::table('users_kpi_counts')->where('id' , $id)->update(array(
                    'game_score'=>$score,
                    'updated_at'=>$this->now,
                ));
//                if($score>=1500)
//                {
//                    GreatUserScoreUpdate::dispatch($userId , 'gameIII' , $snowId)->onQueue('helloo_{great_user_score_update}');
//                }elseif ($score<1500&&$score>=800)
//                {
//                    GreatUserScoreUpdate::dispatch($userId , 'gameII' , $snowId)->onQueue('helloo_{great_user_score_update}');
//                }elseif ($score<800&&$score>=300)
//                {
//                    GreatUserScoreUpdate::dispatch($userId , 'gameI' , $snowId)->onQueue('helloo_{great_user_score_update}');
//                }
            }
        }
    }

}
