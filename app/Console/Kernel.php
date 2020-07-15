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
//        \App\Console\Commands\CalculatingRate::class,
        \App\Console\Commands\CalculatingRateLimit::class,
        \App\Console\Commands\GeneratePostView::class,
        \App\Console\Commands\GenerateYesterdayUserRank::class,
        \App\Console\Commands\AutoIncreasePostView::class,
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
//        $schedule->command('calculating:rate')
//            ->hourlyAt('30');
        $schedule->command('calculating:rate_limit')
            ->hourlyAt('15');
        $schedule->command('calculating:rate_limit')
            ->hourlyAt('45');
        $schedule->command('generate:post_view')
            ->dailyAt('01:00');
        $schedule->command('generate:yesterday_user_rank')
            ->daily();
        $schedule->command('auto:increase_post_view')
            ->dailyAt('02:00');
        $schedule->command('generate:post_essence_rank')
            ->mondays()->dailyAt('20:00');
        $schedule->command('generate:post_essence_rank')
            ->wednesdays()->dailyAt('20:00');
        $schedule->command('generate:post_essence_rank')
            ->fridays()->dailyAt('20:00');
        $schedule->command('generate:post_essence_rank')
            ->sundays()->dailyAt('20:00');
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
