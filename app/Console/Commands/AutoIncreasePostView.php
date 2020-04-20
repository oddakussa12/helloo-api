<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Repositories\Contracts\PostRepository;

class AutoIncreasePostView extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:increase_post_view';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto increase post view';

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
        app(PostRepository::class)->autoIncreasePostView();
    }
}
