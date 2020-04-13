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
        \App\Console\Commands\GeneratePostCommentNumRank::class,
        \App\Console\Commands\GenerateAutoIncreasePostView::class,
        \App\Console\Commands\GeneratePostEssenceRank::class,
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
        $schedule->command('calculating:rate')
            ->everyThirtyMinutes();
        $schedule->command('generate:user_rank')
            ->daily();
        $schedule->command('generate:post_comment_num_rank')
            ->daily();
        $schedule->command('generate:auto_increase_post_view')
            ->daily();
        $schedule->command('generate:post_essence_rank')
            ->mondays();
        $schedule->command('generate:post_essence_rank')
            ->wednesdays();
        $schedule->command('generate:post_essence_rank')
            ->fridays();
        $schedule->command('generate:post_essence_rank')
            ->sundays();
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
