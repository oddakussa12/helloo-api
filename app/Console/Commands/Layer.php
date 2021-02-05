<?php

namespace App\Console\Commands;

use App\Custom\Uuid\RandomStringGenerator;
use App\Models\User;
use App\Traits\CachableUser;
use App\Jobs\Test as TestJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Ramsey\Uuid\Uuid;
use App\Foundation\Auth\User\Update;
use App\Repositories\Contracts\UserRepository;


class Layer extends Command
{
    use CachableUser,Update;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:layer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Layer test';

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
        $this->help020401();
    }

    public function help020401()
    {
        $now = Carbon::now();
        $file = date('H-i-s').'.csv';
        file_put_contents($file  , chr(239).chr(187).chr(191)."日期,数量,激活数量".PHP_EOL , FILE_APPEND);
        for ($i=1;$i<=15;$i++)
        {
            $day =  $now->subDays(1)->toDateString();
            $count = DB::table('users')->where('user_created_at' , "<=" , $day." 23:59:59")->where('user_created_at' , ">=" , $day." 00:00:00")->count();
            $activation = DB::table('users')->where('user_activation' , 1)->where('user_created_at' , "<=" , $day." 23:59:59")->where('user_created_at' , ">=" , $day." 00:00:00")->count();
            file_put_contents($file  , $day.','.$count.','.$activation.PHP_EOL , FILE_APPEND);
        }

    }

    public function help0204()
    {
        $query = [
            55420,56171,56707,1536168042,1992471450
        ];
        $users = DB::table('users')->whereIn('user_id' , $query)->get();
        $file = date('H-i-s').'.csv';
        file_put_contents($file  , chr(239).chr(187).chr(191)."ID,昵称,好友ID,好友昵称,好友好友数".PHP_EOL , FILE_APPEND);
        foreach ($users as $user)
        {
            $friendIds = DB::table('users_friends')->where('user_id' , $user->user_id)->select('friend_id')->distinct()->pluck('friend_id')->all();
            foreach ($friendIds as $friendId)
            {
                $friend = DB::table('users')->where('user_id' , $friendId)->first();
                $count = DB::table('users_friends')->where('user_id' , $friendId)->count();
                file_put_contents($file  , $user->user_id.','.$user->user_nick_name.','.$friend->user_id.','.$friend->user_nick_name.','.$count.PHP_EOL , FILE_APPEND);
            }
        }
    }

    public function help0203()
    {
        $query = array(
            1,
            2,
            61,
            63,
            64,
            65,
            66,
            67,
            68,
            69
        );
        $userIds = DB::table('users')->whereIn('user_nick_name' , array('x.shamira.x', 'sweetie14', 'sheneal', 'KiannaMarriott', 'JavyLeReaper'))->pluck('user_id')->all();
        $phones = DB::table('users_phones')->whereIn('user_id' , $userIds)->pluck('user_phone')->all();
        $query = array_unique(DB::table('users_phones')->whereIn('user_phone' , $phones)->pluck('user_id')->all());
        $oneFriendIds = DB::table('users_friends')->whereIn('user_id' , $query)->select('friend_id')->distinct()->pluck('friend_id')->all();
        dump($oneFriendIds);
        $twoFriendIds = DB::table('users_friends')->whereIn('user_id' , $oneFriendIds)->select('friend_id')->distinct()->pluck('friend_id')->all();
        dump($twoFriendIds);
        $threeFriendIds = DB::table('users_friends')->whereIn('user_id' , $twoFriendIds)->select('friend_id')->distinct()->pluck('friend_id')->all();
        dump($threeFriendIds);
        $fourFriendIds = DB::table('users_friends')->whereIn('user_id' , $threeFriendIds)->select('friend_id')->distinct()->pluck('friend_id')->all();
        dump($fourFriendIds);
        $fiveFriendIds = DB::table('users_friends')->whereIn('user_id' , $fourFriendIds)->select('friend_id')->distinct()->pluck('friend_id')->all();
        dump($fiveFriendIds);
        $sixFriendIds = DB::table('users_friends')->whereIn('user_id' , $fiveFriendIds)->select('friend_id')->distinct()->pluck('friend_id')->all();
        dump($sixFriendIds);
        $friendIds = collect(array_diff(array_unique(array_merge($query , $oneFriendIds , $twoFriendIds , $threeFriendIds , $fourFriendIds , $fiveFriendIds , $sixFriendIds)) , $query))->chunk(100)->toArray();
        $file = date('H:i:s').'.csv';
        file_put_contents($file  , chr(239).chr(187).chr(191)."用户ID,用户名,昵称,好友数".PHP_EOL , FILE_APPEND);
        foreach ($friendIds as $friendId)
        {
            $userIds = collect($friendId)->toArray();
            $users = DB::table('users')->whereIn('user_id' , $userIds)->get();
            foreach ($users as $user)
            {
                $count = DB::table('users_friends')->where('user_id' , $user->user_id)->count();
                $str = $user->user_id.','.$user->user_name.','.$user->user_nick_name.','.$count.PHP_EOL;
                file_put_contents($file  , $user->user_id.','.$user->user_name.','.$user->user_nick_name.','.$count.PHP_EOL , FILE_APPEND);
            }
        }

    }


    public function toCSV(array $data, array $colHeaders = array(), $asString = false) {
        $stream = ($asString)
            ? fopen("php://temp/maxmemory", "w+")
            : fopen("php://output", "w");

        if (!empty($colHeaders)) {
            fputcsv($stream, $colHeaders);
        }

        foreach ($data as $record) {
            fputcsv($stream, $record);
        }

        if ($asString) {
            rewind($stream);
            $returnVal = stream_get_contents($stream);
            fclose($stream);
            return $returnVal;
        }
        else {
            fclose($stream);
        }
    }





}
