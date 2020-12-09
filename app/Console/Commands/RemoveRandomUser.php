<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Custom\RedisList;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;


class RemoveRandomUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:random_user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove Random User';

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
        $this->removeVoice();
        $this->removeVideo();
    }

    public function removeVoice()
    {
        $now = Carbon::now();
        $ago = $now->subSeconds(25);
        $setKey = 'helloo:account:service:account-random-voice-set';
        $officialSetKey = 'helloo:account:service:account-random-official-voice-set';
        $filterSetKey = 'helloo:account:service:account-random-voice-filter-set';
        $sortSetKey = 'helloo:account:service:account-random-voice-sort-set';
        $perPage = 100;
        $count = Redis::zcard($sortSetKey);
        $redis = new RedisList();
        $lastPage = ceil($count/$perPage);
        for ($page=1;$page<=$lastPage;$page++) {
            $userIds = array();
            $offset = ($page - 1) * $perPage;
            $users = $redis->zRevRangeByScore($sortSetKey, $ago->timestamp, '-inf' , true, array($offset, $perPage));
            if (empty($users)) {
                break;
            }
            foreach ($users as $userId=>$time)
            {
                array_push($userIds , $userId);
            }
            Redis::srem($setKey , $userIds);
            Redis::srem($filterSetKey , $userIds);
            Redis::srem($officialSetKey , $userIds);
        }
    }

    public function removeVideo()
    {
        $now = Carbon::now();
        $ago = $now->subSeconds(25);
        $setKey = 'helloo:account:service:account-random-video-set';
        $officialSetKey = 'helloo:account:service:account-random-official-video-set';
        $filterSetKey = 'helloo:account:service:account-random-video-filter-set';
        $sortSetKey = 'helloo:account:service:account-random-video-sort-set';

        $set00Key = 'helloo:account:service:account-random-video-filter-set-00';
        $set01Key = 'helloo:account:service:account-random-video-filter-set-01';
        $set02Key = 'helloo:account:service:account-random-video-filter-set-02';
        $set10Key = 'helloo:account:service:account-random-video-filter-set-10';
        $set11Key = 'helloo:account:service:account-random-video-filter-set-11';
        $set12Key = 'helloo:account:service:account-random-video-filter-set-12';


        $perPage = 100;
        $count = Redis::zcard($sortSetKey);
        $redis = new RedisList();
        $lastPage = ceil($count/$perPage);
        for ($page=1;$page<=$lastPage;$page++) {
            $userIds = array();
            $offset = ($page - 1) * $perPage;
            $users = $redis->zRevRangeByScore($sortSetKey, $ago->timestamp, '-inf' , true, array($offset, $perPage));
            if (empty($users)) {
                break;
            }
            foreach ($users as $userId=>$time)
            {
                array_push($userIds , $userId);
            }
            Redis::srem($setKey , $userIds);
            Redis::srem($filterSetKey , $userIds);
            Redis::srem($officialSetKey , $userIds);
            Redis::srem($set00Key , $userIds);
            Redis::srem($set01Key , $userIds);
            Redis::srem($set02Key , $userIds);
            Redis::srem($set10Key , $userIds);
            Redis::srem($set11Key , $userIds);
            Redis::srem($set12Key , $userIds);
        }
    }

}
