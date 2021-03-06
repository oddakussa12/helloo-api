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
        \App\Console\Commands\Dau::class,
        \App\Console\Commands\Retention::class,
//        \App\Console\Commands\RealTimeChatDepth::class,
        \App\Console\Commands\ActiveUser::class,
        \App\Console\Commands\FixUserCountry::class,
        \App\Console\Commands\GenerateDiscovery::class
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

        //dau
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
        $schedule->command('generate:dau id')
            ->dailyAt(16)->when(function(){
                return config('common.cron_switch');
            });
        $schedule->command('generate:dau et')
            ->dailyAt(12)->when(function(){
                return config('common.cron_switch');
            });

        //retention
        $schedule->command('generate:retention tl')
            ->dailyAt("18:10")->when(function(){
                return config('common.cron_switch');
            });
        $schedule->command('generate:retention gd')
            ->dailyAt("18:20")->when(function(){
                return config('common.cron_switch');
            });
        $schedule->command('generate:retention mu')
            ->dailyAt("18:30")->when(function(){
                return config('common.cron_switch');
            });
        $schedule->command('generate:retention id')
            ->dailyAt("18:40")->when(function(){
                return config('common.cron_switch');
            });
        $schedule->command('generate:retention et')
            ->dailyAt("18:50")->when(function(){
                return config('common.cron_switch');
            });

        //chatDepth 5
//        $schedule->command('real:time_chat_depth' , array('yesterday'))
//            ->cron('45 */3 * * *')->when(function(){
//                return config('common.cron_switch');
//            });
//        $schedule->command('real:time_chat_depth' , array('today'))
//            ->hourlyAt(10)->when(function(){
//                return config('common.cron_switch');
//            });
//        $schedule->command('real:time_chat_depth')
//            ->dailyAt('12:30')->when(function(){
//                return config('common.cron_switch');
//            });

        $schedule->command('active:user')
            ->dailyAt('18:00')->when(function(){
                return config('common.cron_switch');
            });

        $schedule->command('fix:user_country')
            ->dailyAt('21:00')->when(function(){
                return config('common.cron_switch');
            });

        $schedule->command('generate:discovery' , array('popular'))
            ->weekends()->when(function(){
                return config('common.cron_switch');
            });
        $schedule->command('generate:discovery' , array('rated'))
            ->daily()->when(function(){
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
