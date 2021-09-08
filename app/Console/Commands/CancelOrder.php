<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CancelOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:remove_order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto Remove Order';

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
        $time = Carbon::now()->subHours(12)->toDateTimeString();
        DB::table('orders')->where('status' , 0)->where('created_at' , '<=' , $time)->orderByDesc('created_at')->chunk(100 , function($orders){
            foreach ($orders as $k=> $order)
            {
                $order->operator = "auto";
                $orders[$k] = $order;
            }
            dump($orders);
        });
    }
}