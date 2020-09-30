<?php

namespace App\Console\Commands;

use App\Custom\Constant\Constant;
use App\Jobs\FriendSignIn;
use App\Models\UserFriendSignIn;
use App\Traits\CachableUser;
use App\Jobs\Test as TestJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;


class TestRedis extends Command
{
    use CachableUser;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto test';

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
        $data['user_id'] = 28480;
        $data['friend_id'] = 422887;
        $data['sign_month'] = 202009;
        $yestday = strtotime(date('Ymd')) -86400;
        for ($i=0;$i<10;$i++) {
            $data['sign_day'] = $yestday - ($i*86400);
        }

       $result = UserFriendSignIn::updateOrCreate(['id'=>null], $data);
        dump($result);
        exit;
        $arr = [
            Constant::RY_CHAT_FRIEND_SIGN_IN. '*',
            Constant::RY_CHAT_FRIEND_IS_FRIEND. '*',
            Constant::RY_CHAT_FRIEND_RELATIONSHIP. '*'
        ];
        foreach ($arr as $item) {
            $value1 = Redis::keys($item);
            if (!empty($value1)) {
                foreach ($value1 as $item2) {
                    dump($item2);
                    Redis::del($item2);
                }
            }
        }


    }

}
