<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class FriendFromDifferentSchool implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private $user;
    private $friendId;

    public function __construct($user , $friendId)
    {
        $this->user = $user;
        $this->friendId = $friendId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $friend = app(UserRepository::class)->findByUserId($this->friendId);
        if($friend->user_sl!=$this->user->user_sl&&$friend->user_sl!='other'&&$this->user->user_sl!='other')
        {
            $flag = DB::table('users_friends')->join('users' , function ($user) use ($friend){
                $user->on('users.user_id' , 'users_friends.friend_id')->where('user_sl' , $friend->user_sl);
            })->where('user_id' , $this->user->user_id)->first();
            if(blank($flag))
            {
                GreatUserScoreUpdate::dispatch($this->user->user_id , 'otherSchoolFriend' , $this->friendId)->onQueue('helloo_{great_user_score_update}');
                GreatUserScoreUpdate::dispatch($this->friendId , 'otherSchoolFriend' , $this->user->user_id)->onQueue('helloo_{great_user_score_update}');
            }
        }
    }

}
