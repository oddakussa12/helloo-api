<?php

namespace App\Console\Commands;



use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ScoreInit extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'score:init';

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
        $now = date('Y-m-d H:i:s');
        DB::table('users')
            ->where('user_activation' , 1)
            ->where('user_created_at' , '<=' , $now)
            ->orderByDesc('user_id')
            ->chunk(100 , function ($users) use ($now){
                foreach ($users as $user)
                {
                    $data = array();
                    $score = 0;
                    $userId = $user->user_id;
                    $avatar = $bg = $user_sl = $about = $name = false;
                    if($user->user_avatar!='default_avatar.jpg')
                    {
                        array_push($data , array(
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
                            'user_id'=>$userId,
                            'type'=>'fillName',
                            'score'=>5,
                            'created_at'=>$now,
                        ));
                        $score = $score+5;
                    }
                    $friendCount = DB::table('users_friends')->where('user_id' , $user->user_id)->count();
                    if($friendCount>=10)
                    {
                        array_push($data , array(
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
                        DB::table('users_scores_logs_'.$this->hashDbIndex($user->user_id))->insert($data);
                    }
                    $score>0&&DB::table('users_scores')->insert(array(
                        'user_id'=>$userId,
                        'init'=>$score,
                        'score'=>$score,
                        'created_at'=>$now,
                    ));
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

    private function hashDbIndex($string , $hashNumber=8)
    {
        $checksum  = crc32(md5(strtolower(strval($string))));
        if(8==PHP_INT_SIZE){
            if($checksum >2147483647){
                $checksum  = $checksum&(2147483647);
                $checksum = ~($checksum-1);
                $checksum = $checksum&2147483647;
            }
        }
        return (abs($checksum) % intval($hashNumber));
    }

}
