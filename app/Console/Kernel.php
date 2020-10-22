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
//        \App\Console\Commands\CalculatingRateLimit::class,
        \App\Console\Commands\CalculatingRateLimitV2::class,
        \App\Console\Commands\GeneratePostView::class,
        \App\Console\Commands\GenerateYesterdayUserRank::class,
//        \App\Console\Commands\AutoIncreasePostView::class,
//        \App\Console\Commands\GeneratePostEssenceRank::class,
//        \App\Console\Commands\AutoUpdateOnlineUser::class,
        \App\Console\Commands\Promote::class,
        \App\Console\Commands\AllUserNotification::class,
        \App\Console\Commands\StoreVisitLog::class,
        \Torann\GeoIP\Console\Update::class,
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
//            ->hourlyAt('30')->when(function(){
//                return config('common.cron_switch');
//            });
        $schedule->command('all:user_notification')
            ->everyTenMinutes()->when(function(){
                return config('common.cron_switch');
            });
        $schedule->command('calculating:rate_limit_v2')
            ->hourlyAt('20')->when(function(){
                return config('common.cron_switch');
            });
        $schedule->command('calculating:rate_limit_v2')
            ->hourlyAt('50')->when(function(){
                return config('common.cron_switch');
            });
//        $schedule->command('calculating:rate_limit')
//            ->hourlyAt('15')->when(function(){
//                return config('common.cron_switch');
//            });
//        $schedule->command('calculating:rate_limit')
//            ->hourlyAt('45')->when(function(){
//                return config('common.cron_switch');
//            });
        $schedule->command('store:visit_log')
            ->dailyAt('17:00')->when(function(){
                return config('common.cron_switch');
            });
        $schedule->command('generate:post_view')
            ->dailyAt('01:00')->when(function(){
                return config('common.cron_switch');
            });
        $schedule->command('generate:yesterday_user_rank')
            ->daily()->when(function(){
                return config('common.cron_switch');
            });
//        $schedule->command('auto:increase_post_view')
//            ->dailyAt('02:00')->when(function(){
//                return config('common.cron_switch');
//            });
//        $schedule->command('generate:post_essence_rank')
//            ->mondays()->dailyAt('20:00')->when(function(){
//                return config('common.cron_switch');
//            });
//        $schedule->command('generate:post_essence_rank')
//            ->wednesdays()->dailyAt('20:00')->when(function(){
//                return config('common.cron_switch');
//            });
//        $schedule->command('generate:post_essence_rank')
//            ->fridays()->dailyAt('20:00')->when(function(){
//                return config('common.cron_switch');
//            });
//        $schedule->command('generate:post_essence_rank')
//            ->sundays()->dailyAt('20:00')->when(function(){
//                return config('common.cron_switch');
//            });
//        $schedule->command('auto:update_online_user')
//            ->everyFiveMinutes()->when(function(){
//                return config('common.cron_switch');
//            });
//        $schedule->command('promote:push')
//            ->dailyAt('12:00')->when(function(){
//                return config('common.cron_switch');
//            });
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
