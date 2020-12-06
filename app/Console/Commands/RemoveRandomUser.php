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
        $voiceSetKey = 'helloo:account:service:account-random-voice-set';
        $voiceFilterSetKey = 'helloo:account:service:account-random-voice-filter-set';
        $voiceSortSetKey = 'helloo:account:service:account-random-voice-sort-set';
        $perPage = 100;
        $count = Redis::zcard($voiceSortSetKey);
        $redis = new RedisList();
        $lastPage = ceil($count/$perPage);
        for ($page=1;$page<=$lastPage;$page++) {
            $userIds = array();
            $offset = ($page - 1) * $perPage;
            $users = $redis->zRevRangeByScore($voiceSortSetKey, $ago->timestamp, '-inf' , true, array($offset, $perPage));
            if (empty($users)) {
                break;
            }
            foreach ($users as $userId=>$time)
            {
                array_push($userIds , $userId);
            }
            Redis::srem($voiceSetKey , $userIds);
            Redis::srem($voiceFilterSetKey , $userIds);
        }
    }

    public function removeVideo()
    {
        $now = Carbon::now();
        $ago = $now->subSeconds(25);
        $videoSetKey = 'helloo:account:service:account-random-video-set';
        $videoFilterSetKey = 'helloo:account:service:account-random-video-filter-set';
        $videoSortSetKey = 'helloo:account:service:account-random-video-sort-set';
        $perPage = 100;
        $count = Redis::zcard($videoSortSetKey);
        $redis = new RedisList();
        $lastPage = ceil($count/$perPage);
        for ($page=1;$page<=$lastPage;$page++) {
            $userIds = array();
            $offset = ($page - 1) * $perPage;
            $users = $redis->zRevRangeByScore($videoSortSetKey, $ago->timestamp, '-inf' , true, array($offset, $perPage));
            if (empty($users)) {
                break;
            }
            foreach ($users as $userId=>$time)
            {
                array_push($userIds , $userId);
            }
            Redis::srem($videoSetKey , $userIds);
            Redis::srem($videoFilterSetKey , $userIds);
        }
    }

}
