<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Repositories\Contracts\PostRepository;

class GenerateFinePostCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:fine_post_cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'generate fine post cache';

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
        Cache::forget('fine_post');
        app(PostRepository::class)->getFinePostIds();
    }
}
