<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Repositories\Contracts\UserRepository;

class FriendFromDifferentSchool implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $user;
    private $friendId;
    /**
     * @var false|string
     */
    private $now;
    /**
     * @var mixed|string
     */
    private $type;

    public function __construct($user , $friendId , $type="accept")
    {
        $this->user = $user;
        $this->friendId = $friendId;
        $this->type = $type;
        $this->now = date('Y-m-d H:i:s');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $friend = app(UserRepository::class)->findByUserId($this->friendId)->toArray();
//        Log::info('$friend' , $friend);
        if($this->type=='accept')
        {
//            Log::info('user' , array($this->user->user_sl , $friend['user_sl']));
            if($friend['user_sl']!=$this->user->user_sl&&$friend['user_sl']!='other'&&$this->user->user_sl!='other')
            {
//                $flag = DB::table('users_friends')->join('users' , function ($user) use ($friend){
//                    $user->on('users.user_id' , 'users_friends.friend_id')->where('user_sl' , $friend['user_sl']);
//                })->where('users_friends.user_id' , $this->user->user_id)->first();
//                Log::info('$flag' , array($flag));
//                if(blank($flag))
//                {
                    $uCounts = DB::table('users_kpi_counts')->where('user_id' , $this->user->user_id)->first();
                    if(blank($uCounts))
                    {
                        DB::table('users_kpi_counts')->insertGetId((array(
                            'user_id'=>$this->user->user_id,
                            'other_school_friend'=>1,
                            'created_at'=>$this->now,
                            'updated_at'=>$this->now,
                        )));
                    }else{
                        $id = $uCounts->id;
                        DB::table('users_kpi_counts')->where('id' , $id)->increment('other_school_friend' , 1 , array(
                            'updated_at'=>$this->now,
                        ));
                    }
                    $fCounts = DB::table('users_kpi_counts')->where('user_id' , $friend['user_id'])->first();
                    if(blank($fCounts))
                    {
                        DB::table('users_kpi_counts')->insertGetId((array(
                            'user_id'=>$friend['user_id'],
                            'other_school_friend'=>1,
                            'created_at'=>$this->now,
                            'updated_at'=>$this->now,
                        )));
                    }else{
                        $id = $fCounts->id;
                        DB::table('users_kpi_counts')->where('id' , $id)->increment('other_school_friend' , 1 , array(
                            'updated_at'=>$this->now,
                        ));
                    }
//                    if (empty($uCounts) || $uCounts->other_school_friend<1) {
//                        GreatUserScoreUpdate::dispatch($this->user->user_id , 'otherSchoolFriend' , $this->friendId)->onQueue('helloo_{great_user_score_update}');
//                    }
//                    if (empty($fCounts) || $fCounts->other_school_friend<1) {
//                        GreatUserScoreUpdate::dispatch($this->friendId , 'otherSchoolFriend' , $this->user->user_id)->onQueue('helloo_{great_user_score_update}');
//                    }
//                }
            }
            $counts = DB::table('users_kpi_counts')->where('user_id' , $this->user->user_id)->first();
            if(blank($counts))
            {
                DB::table('users_kpi_counts')->insertGetId((array(
                    'user_id'=>$this->user->user_id,
                    'friend'=>1,
                    'created_at'=>$this->now,
                    'updated_at'=>$this->now,
                )));
            }else{
                $id = $counts->id;
                DB::table('users_kpi_counts')->where('id' , $id)->increment('friend' , 1 , array(
                    'updated_at'=>$this->now,
                ));
            }
            $counts = DB::table('users_kpi_counts')->where('user_id' , $friend['user_id'])->first();
            if(blank($counts))
            {
                DB::table('users_kpi_counts')->insertGetId((array(
                    'user_id'=>$friend['user_id'],
                    'friend'=>1,
                    'created_at'=>$this->now,
                    'updated_at'=>$this->now,
                )));
            }else{
                $id = $counts->id;
                DB::table('users_kpi_counts')->where('id' , $id)->increment('friend' , 1 , array(
                    'updated_at'=>$this->now,
                ));
            }
        }else{
            $counts = DB::table('users_kpi_counts')->where('user_id' , $this->user->user_id)->first();
            if(blank($counts))
            {
//                DB::table('users_kpi_counts')->insertGetId((array(
//                    'user_id'=>$this->user->user_id,
//                    'friend'=>1,
//                    'created_at'=>$this->now,
//                    'updated_at'=>$this->now,
//                )));
            }else{
                $id = $counts->id;
                DB::table('users_kpi_counts')->where('id' , $id)->decrement('friend' , 1 , array(
                    'updated_at'=>$this->now,
                ));
            }
            $counts = DB::table('users_kpi_counts')->where('user_id' , $friend['user_id'])->first();
            if(blank($counts))
            {
//                DB::table('users_kpi_counts')->insertGetId((array(
//                    'user_id'=>$friend['user_id'],
//                    'friend'=>1,
//                    'created_at'=>$this->now,
//                    'updated_at'=>$this->now,
//                )));
            }else{
                $id = $counts->id;
                DB::table('users_kpi_counts')->where('id' , $id)->decrement('friend' , 1 , array(
                    'updated_at'=>$this->now,
                ));
            }
        }

    }

}
