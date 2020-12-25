<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class ClearRyTokenCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:ry_token_cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear ry token cache';

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
        for($i=1;$i<=1000;$i++)
        {
            $key = 'helloo:account:service:account-ry-token:'.$i;
            Redis::del($key);
        }
    }

}
