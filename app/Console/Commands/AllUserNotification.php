<?php

namespace App\Console\Commands;



use Illuminate\Console\Command;


class AllUserNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'all:user_notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'All User Notification';

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

    }

}
