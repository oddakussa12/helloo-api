<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class CalculatingRete extends Command
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
    protected $description = 'Calculating post rate';

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
        //
        file_put_contents('1.txt' , 'd'.date('Y-m-d H:i:s').PHP_EOL , FILE_APPEND);
    }
}
