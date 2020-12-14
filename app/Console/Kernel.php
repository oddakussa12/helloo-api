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
        \Torann\GeoIP\Console\Update::class,
        \App\Console\Commands\RemoveRandomUser::class,
        \App\Console\Commands\StoreVisitLog::class,
        \App\Console\Commands\StoreStatusLog::class,
        \App\Console\Commands\RemoveExpiredOnlineUser::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('store:visit_log')
            ->dailyAt('20:00')->when(function(){
                return config('common.cron_switch');
            });
        $schedule->command('store:status_log')
            ->dailyAt('19:00')->when(function(){
                return config('common.cron_switch');
            });
        $schedule->command('remove:expired_online_user')
            ->hourly()->when(function(){
                return config('common.cron_switch');
            });
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
