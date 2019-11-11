<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Repositories\Contracts\UserRepository;

class GenerateUserRank extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:user_rank';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'generate user rank';

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
        Cache::forget('user_rank');
        app(UserRepository::class)->getActiveUserId();
    }
}
