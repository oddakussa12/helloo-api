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
        \App\Console\Commands\IpCacheUpdate::class,
        \App\Console\Commands\RemoveRandomUser::class,
        \App\Console\Commands\StoreVisitLog::class,
        \App\Console\Commands\StoreStatusLog::class,
        \App\Console\Commands\RemoveExpiredOnlineUser::class,
        \App\Console\Commands\GenerateUid::class,
        \App\Console\Commands\Schema::class,
        \App\Console\Commands\Message::class,
        \App\Console\Commands\Dau::class
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
            ->dailyAt('17:00')->when(function(){
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
        $schedule->command('generate:uid')
            ->hourlyAt(6)->when(function(){
                return config('common.cron_switch');
            });
        $schedule->command('schema:start')
            ->monthlyOn(15)->when(function(){
                return config('common.cron_switch');
            });
        $schedule->command('ip_cache:update')
            ->daily();
        $schedule->command('message:send')
            ->everyMinute();
        $schedule->command('generate:dau tl')
            ->dailyAt(18)->when(function(){
                return config('common.cron_switch');
            });
        $schedule->command('generate:dau gd')
            ->dailyAt(5)->when(function(){
                return config('common.cron_switch');
            });
        $schedule->command('generate:dau mu')
            ->dailyAt(21)->when(function(){
                return config('common.cron_switch');
            });
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
