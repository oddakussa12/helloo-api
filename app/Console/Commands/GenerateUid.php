<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Custom\Uuid\RandomStringGenerator;

class GenerateUid extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:uid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Uid';

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
     * @return mixed
     */
    public function handle()
    {
        $key = 'helloo:account:service:account-user-name-unique-set';
        $foreverKey = 'helloo:account:service:account-user-name-unique-set-forever';
        $data = array();
        $uidCount = Redis::scard($key);
        if($uidCount<10000)
        {
            $generator = new RandomStringGenerator(implode(range(1, 9)).implode(range('a', 'z')));
            for($i=1;$i<=200000;$i++)
            {
                $username = 'lb_'.$generator->generate(12);
                if(!Redis::sismember($foreverKey , $username))
                {
                    array_push($data , array('username'=>$username));
                }
                if($i%500==0)
                {
                    !blank($data)&&DB::table('unique_usernames')->insert($data);
                    !blank($data)&&Redis::sadd($key , array_column($data , 'username'));
                    !blank($data)&&Redis::sadd($foreverKey , array_column($data , 'username'));
                    $data = array();
                }
            }
        }


        $idKey = 'helloo:account:service:account-user-id-unique-set';
        $foreverIdKey = 'helloo:account:service:account-user-id-unique-set-forever';
        $idCount = intval(Redis::scard($idKey));
        $data = array();
        if($idCount<10000)
        {
            for($i=1;$i<=200000;$i++)
            {
                $uid = mt_rand(1111111111 , 2122222222);
                if(!Redis::sismember($foreverIdKey , $uid))
                {
                    array_push($data , $uid);
                }
                if($i%5000==0)
                {
                    !blank($data)&&Redis::sadd($idKey , $data);
                    !blank($data)&&Redis::sadd($foreverIdKey , $data);
                    $data = array();
                }
            }
        }


    }
}
