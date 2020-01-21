<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        \App\Console\Commands\CalculatingRate::class,
        \App\Console\Commands\GenerateUserRank::class,
//        \App\Console\Commands\GeneratePostIdRank::class,
        \App\Console\Commands\GeneratePostCommentNumRank::class,
//        \App\Console\Commands\GenerateFinePostCache::class,
        \App\Console\Commands\GeneratePostAuto::class,
        \App\Console\Commands\GenerateUserFollow::class,
        \App\Console\Commands\GenerateAutoIncreasePostView::class,
        \Torann\GeoIP\Console\Update::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        $schedule->command('calculating:rate')
            ->everyThirtyMinutes();
        $schedule->command('generate:user_rank')
            ->daily();
//        $schedule->command('generate:post_id_rank')
//            ->everyFiveMinutes();
        $schedule->command('generate:post_comment_num_rank')
            ->daily();
//        $schedule->command('generate:fine_post_cache')
//            ->everyTenMinutes();

        $schedule->command('generate:auto_store_post')
            ->hourly();
        $schedule->command('generate:user_follow')
            ->hourly();
        $schedule->command('generate:auto_increase_post_view')
            ->daily();
        $schedule->command('geoip:update')
            ->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
