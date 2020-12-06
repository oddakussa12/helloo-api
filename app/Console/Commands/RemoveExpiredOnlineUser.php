<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Custom\RedisList;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;


class RemoveExpiredOnlineUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:expired_online_user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove Expired Online User';

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
        $this->removeUser();
    }

    public function removeUser()
    {
        $now = Carbon::now();
        $ago = $now->subHours(4);
        $imKey = 'helloo:account:service:account-random-im-set';
        $lastActivityTime = 'helloo:account:service:account-ry-last-activity-time';
        $perPage = 100;
        $count = Redis::zcard($lastActivityTime);
        $redis = new RedisList();
        $lastPage = ceil($count/$perPage);
        for ($page=1;$page<=$lastPage;$page++) {
            $userIds = array();
            $offset = ($page - 1) * $perPage;
            $users = $redis->zRevRangeByScore($lastActivityTime, $ago->timestamp, '-inf' , true, array($offset, $perPage));
            if (empty($users)) {
                break;
            }
            foreach ($users as $userId=>$time)
            {
                array_push($userIds , $userId);
            }
            Redis::srem($imKey , $userIds);
        }
    }

}
