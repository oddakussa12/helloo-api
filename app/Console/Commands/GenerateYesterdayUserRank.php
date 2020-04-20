<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Repositories\Contracts\UserRepository;

class GenerateYesterdayUserRank extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:yesterday_user_rank';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'generate yesterday user rank';

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
        app(UserRepository::class)->generateYesterdayUserRank();
    }
}
