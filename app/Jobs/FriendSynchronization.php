<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class FriendSynchronization implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $user;
    /**
     * @var mixed|string
     */
    private $friend;

    /**
     * UserSynchronization constructor.
     * @param $user
     * @param $friend
     */
    public function __construct($user , $friend)
    {
        $this->user = $user;
        $this->friend = $friend;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $now = time();
        $userFriendCount = DB::table('users_friends')->where('user_id' , $this->user)->count();
        $friendFriendCount = DB::table('users_friends')->where('user_id' , $this->friend)->count();
        $userFriend = DB::table('users_friends_counts')->where('user_id' , $this->user)->first();
        $friendFriend = DB::table('users_friends_counts')->where('user_id' , $this->friend)->first();
        if(empty($userFriend))
        {
            DB::table('users_friends_counts')->insert(array(
                'user_id'=>$this->user,
                'friend'=>$userFriendCount,
                'created_at'=>$now,
                'updated_at'=>$now,
            ));
        }else{
            DB::table('users_friends_counts')->where('user_id' , $this->user)->update(array(
                'friend'=>$userFriendCount,
                'updated_at'=>$now
            ));
        }
        if(empty($friendFriend))
        {
            DB::table('users_friends_counts')->insert(array(
                'user_id'=>$this->friend,
                'friend'=>$friendFriendCount,
                'created_at'=>$now,
                'updated_at'=>$now,
            ));
        }else{
            DB::table('users_friends_counts')->where('user_id' , $this->friend)->update(array(
                'friend'=>$friendFriendCount,
                'updated_at'=>$now
            ));
        }

    }

}
