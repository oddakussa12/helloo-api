<?php

namespace App\Console\Commands;



use App\Models\User;
use App\Models\UserKpiCount;
use App\Models\UserScore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ScoreInit extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'score:init {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Score init';

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
        $type = $this->argument('type');
        if ($type =='friend') {
            $this->friendInit();
        }
        if ($type =='otherSchool') {
            $this->otherSchoolInit();
        }
        if ($type =='all') {
             $this->allInit();
        }
    }

    public function otherSchoolInit()
    {
        $time = date('Y-m-d H:i:s');
        $kpis = UserKpiCount::where('other_school_friend', '>', 1)->select('user_id', 'other_school_friend')->get();
        foreach ($kpis as $kpi) {
            $hash  = hashDbIndex($kpi->user_id);
            try{
                DB::beginTransaction();
                $table = 'users_scores_logs_'.$hash;

                $where = ['user_id'=>$kpi->user_id, 'type'=>'otherSchoolFriend'];
                $count = DB::table($table)->where($where)->count();
                if ($count>1) {
                    $score = ($count-1) * 100;
                    $minId = DB::table($table)->select(DB::raw('min(id)'))->where($where)->first();
                    DB::table($table)->where($where)->whereNotIn('id', collect($minId)->toArray())->delete();

                    $userScore = DB::table('users_scores')->where('user_id', $kpi->user_id)->first();
                    $update    = DB::table('users_scores')->where('user_id', $kpi->user_id)->decrement('score', $score, ['updated_at'=>$time]);
                }
                DB::commit();
                // 积分 排行
                $memKey = 'helloo:account:user-score-rank';
                $total  = $userScore->score - $score;
                Redis::zadd($memKey, $total, $kpi->user_id);

            }catch (\Exception $e){
                DB::rollBack();
            }
        }
    }

    public function friendInit()
    {
        $now = date('Y-m-d H:i:s');
        DB::table('users')
            ->where('user_activation' , 1)
            ->where('user_created_at' , '<=' , $now)
            ->where('user_id', '1233392469')
            ->orderByDesc('user_id')
            ->chunk(100 , function ($users) use ($now){
                foreach ($users as $user)
                {
                    $userId = $user->user_id;
                    $hash   = hashDbIndex($userId);
                    $friendCount = DB::table('users_friends')->where('user_id' , $userId)->count();
                    if (empty($friendCount))
                    {
                        return;
                    }
                    $score  = $friendCount*2;
                    $result = DB::table('users_scores_logs_'.$hash)->select(DB::raw('sum(score) score'))->where('type', 'like', 'friend%')->where('user_id', $userId)->first();
                    $info   = UserScore::where('user_id', $userId)->first();
                    if (empty($info)) {
                        $score>0&&DB::table('users_scores')->insert(array(
                            'user_id'=>$userId,
                            'init'=>$score,
                            'score'=>$score,
                            'created_at'=>$now,
                        ));
                    } else {
                        $friend = !empty($result->score) ? $result->score : 0;
                        $score  = $score-$friend;
                        if ($score) {
                            UserScore::where('user_id', $userId)->update(['init'=>$info->init+$score, 'score'=>$info->score+$score,'updated_at'=>$now]);
                            $memKey  = 'helloo:account:user-score-rank';
                            Redis::zadd($memKey, $info->score, $userId);
                        }
                    }
                }
            });

    }

    public function allInit()
    {
        $result = DB::select("select user_id from (select user_id, count(1) num from t_users_scores group by user_id having num > 1) b");
        $uids   = collect($result)->pluck('user_id')->toArray();
        $now    = date('Y-m-d H:i:s');
        DB::table('users')
            ->where('user_activation' , 1)
            ->where('user_created_at' , '<=' , $now)
            ->whereIn('user_id', $uids)
            ->orderByDesc('user_id')
            ->chunk(100 , function ($users) use ($now){
                foreach ($users as $user)
                {
                    $data = array();
                    $score = 0;
                    $userId = $user->user_id;
                    dump($userId. '--------------------'.hashDbIndex($userId));
                    if($user->user_avatar!='default_avatar.jpg')
                    {
                        array_push($data , array(
                            'id'=>app('snowflake')->id(),
                            'user_id'=>$userId,
                            'type'=>'fillAvatar',
                            'score'=>20,
                            'created_at'=>$now,
                        ));
                        $score = $score+20;
                    }
                    if(!blank($user->user_bg))
                    {
                        array_push($data , array(
                            'id'=>app('snowflake')->id(),
                            'user_id'=>$userId,
                            'type'=>'fillCover',
                            'score'=>15,
                            'created_at'=>$now,
                        ));
                        $score = $score+15;
                    }
                    if(!blank($user->user_sl)&&strtolower($user->user_sl)!='other')
                    {
                        array_push($data , array(
                            'id'=>app('snowflake')->id(),
                            'user_id'=>$userId,
                            'type'=>'fillSchool',
                            'score'=>15,
                            'created_at'=>$now,
                        ));
                        $score = $score+15;
                    }
                    if(!blank($user->user_about))
                    {
                        array_push($data , array(
                            'id'=>app('snowflake')->id(),
                            'user_id'=>$userId,
                            'type'=>'fillAbout',
                            'score'=>20,
                            'created_at'=>$now,
                        ));
                        $score = $score+20;
                    }
                    if(!blank($user->user_name)&&substr($user->user_name , 0 , 3)!='lb_')
                    {
                        array_push($data , array(
                            'id'=>app('snowflake')->id(),
                            'user_id'=>$userId,
                            'type'=>'fillName',
                            'score'=>5,
                            'created_at'=>$now,
                        ));
                        $score = $score+5;
                    }

                    $friendCount = DB::table('users_friends')->where('user_id' , $user->user_id)->count();
                    if (!empty($friendCount))
                    {
                        $fCount = $friendCount*2;
                        array_push($data , array(
                            'id'=>app('snowflake')->id(),
                            'user_id'=>$userId,
                            'type'=>'friendAccept',
                            'score'=>$fCount,
                            'created_at'=>$now,
                        ));
                        $score = $score+$fCount;
                    }
                    if($friendCount>=10)
                    {
                        array_push($data , array(
                            'id'=>app('snowflake')->id(),
                            'user_id'=>$userId,
                            'type'=>'socialI',
                            'score'=>30,
                            'created_at'=>$now,
                        ));
                        $score = $score+30;
                    }
                    if($friendCount>=30)
                    {
                        array_push($data , array(
                            'id'=>app('snowflake')->id(),
                            'user_id'=>$userId,
                            'type'=>'socialII',
                            'score'=>150,
                            'created_at'=>$now,
                        ));
                        $score = $score+150;
                    }
                    if($friendCount>=100)
                    {
                        array_push($data , array(
                            'id'=>app('snowflake')->id(),
                            'user_id'=>$userId,
                            'type'=>'socialIII',
                            'score'=>350,
                            'created_at'=>$now,
                        ));
                        $score = $score+350;
                    }
                    $userGameScore = DB::table('users_games_scores')->where('user_id' , $user->user_id)->first();
                    if(!blank($userGameScore))
                    {
                        if($userGameScore->score>=300)
                        {
                            array_push($data , array(
                                'id'=>app('snowflake')->id(),
                                'user_id'=>$userId,
                                'type'=>'talkativeI',
                                'score'=>25,
                                'created_at'=>$now,
                            ));
                            $score = $score+25;
                        }
                        if($userGameScore->score>=800)
                        {
                            array_push($data , array(
                                'id'=>app('snowflake')->id(),
                                'user_id'=>$userId,
                                'type'=>'talkativeII',
                                'score'=>50,
                                'created_at'=>$now,
                            ));
                            $score = $score+50;
                        }
                        if($userGameScore->score>=1500)
                        {
                            array_push($data , array(
                                'id'=>app('snowflake')->id(),
                                'user_id'=>$userId,
                                'type'=>'talkativeIII',
                                'score'=>100,
                                'created_at'=>$now,
                            ));
                            $score = $score+100;
                        }
                    }
                    if(!blank($data))
                    {
                        DB::table('users_scores_logs_'.hashDbIndex($user->user_id))->delete();
                        DB::table('users_scores_logs_'.hashDbIndex($user->user_id))->insert($data);
                    }

                    $info   = UserScore::where('user_id', $userId)->first();
                    if (empty($info)) {
                        $score>0 && DB::table('users_scores')->insert(['user_id'=>$userId, 'init'=>$score, 'score'=>$score, 'created_at'=>$now]);
                    } else {
                        UserScore::where('user_id', $userId)->update(['init'=>$score, 'score'=>$score,'updated_at'=>$now]);
                        $memKey = 'helloo:account:user-score-rank';
                        Redis::zadd($memKey, $score, $userId);
                    }

                    $kpi = DB::table('users_kpi_counts')->where('user_id' , $userId)->first();
                    if(blank($kpi))
                    {
                        DB::table('users_kpi_counts')->insert(array(
                            'user_id'=>$userId,
                            'friend'=>intval($friendCount),
                            'game_score'=>isset($userGameScore->score)?intval($userGameScore->score):0,
                            'created_at'=>$now,
                            'updated_at'=>$now,
                        ));
                    }else{
                        DB::table('users_kpi_counts')->where('user_id' , $userId)->update(array(
                            'friend'=>intval($friendCount),
                            'game_score'=>isset($userGameScore->score)?intval($userGameScore->score):0,
                            'updated_at'=>$now,
                        ));
                    }
                }
            });
    }
}
