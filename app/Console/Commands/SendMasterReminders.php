<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendTelegramReminderToMaster;
use App\Models\Appointment;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendMasterReminders extends Command
{
    protected $signature = 'app:send-master-reminders';

    protected $description = 'Отправка напоминаний мастерам за 15 минут до начала';

    public function handle(): int
    {
        $from = Carbon::now();
        $to = Carbon::now()->addMinutes(15);

        $appointments = Appointment::query()
            ->where('status', Appointment::STATUS_SCHEDULED)
            ->whereNull('reminder_for_master_sent_at')
            ->whereBetween('starts_at', [$from, $to])
            ->with(['master', 'service', 'client'])
            ->get();

        $dispatched = 0;
        foreach ($appointments as $appointment) {
            $master = $appointment->master;
            if ($master && $master->telegram_id) {
                SendTelegramReminderToMaster::dispatch($appointment->id)->onQueue('default');
                $dispatched++;
            }
        }

        $this->info('Master reminders dispatched: '.$dispatched);

        return self::SUCCESS;
    }
}
