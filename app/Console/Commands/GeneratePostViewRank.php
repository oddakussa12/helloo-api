<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Repositories\Contracts\PostRepository;

class GeneratePostViewRank extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:post_view_rank';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate post view rank';

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
        app(PostRepository::class)->generatePostViewRandRank();
    }
}
