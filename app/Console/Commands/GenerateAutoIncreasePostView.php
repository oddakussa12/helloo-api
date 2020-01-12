<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Repositories\Contracts\PostRepository;

class GenerateAutoIncreasePostView extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:auto_increase_post_view';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate auto increase post view';

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
        app(PostRepository::class)->generateAutoIncreasePostView();
    }
}
