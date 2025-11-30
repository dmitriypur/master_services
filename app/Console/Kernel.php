<?php

declare(strict_types=1);

namespace App\Console;

use App\Console\Commands\SendClientReminders;
use App\Console\Commands\SendMasterReminders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        SendMasterReminders::class,
        SendClientReminders::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:send-master-reminders')->everyFiveMinutes();
        $schedule->command('app:send-client-reminders')->everyFiveMinutes();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
