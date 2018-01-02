<?php

namespace App\Console;

use App\Console\Commands\CacheAgentValidCardLog;
use App\Console\Commands\FetchOnlinePlayerCount;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Psy\Command\Command;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //Commands\DataMigrate::class,
        Commands\GenerateDailyStatement::class,
        FetchOnlinePlayerCount::class,
        CacheAgentValidCardLog::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //生成每日数据报表
        $schedule->command('admin:generate-daily-statement')
            ->dailyAt('00:00')
            ->withoutOverlapping()
            ->evenInMaintenanceMode()
            ->appendOutputTo(config('custom.cron_task_log'));
        //获取游戏中在线人数
        $schedule->command('admin:fetch-online-player-count')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->evenInMaintenanceMode()
            ->appendOutputTo(config('custom.cron_task_log'));
        //缓存代理商有效耗卡数据
        $schedule->command('admin:cache-agent-valid-card-log')
            ->everyMinute()
            ->withoutOverlapping()
            ->evenInMaintenanceMode()
            ->appendOutputTo(config('custom.cron_task_log'));
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
