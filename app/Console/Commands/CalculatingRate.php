<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculatingRate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculating:rate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'calculating post rate';

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
        Post::withTrashed()->chunk(10, function ($posts){
            $i = 1;
            foreach ($posts as $post) {
                $post->calculatingRate();

                $i++;
            }
        });
    }
}
