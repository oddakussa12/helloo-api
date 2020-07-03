<?php

namespace App\Console\Commands;

use App\Traits\CachablePost;
use Illuminate\Console\Command;

class CalculatingPostInfo extends Command
{
    use CachablePost;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculating:post_info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'calculating post info';

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

    }
}
