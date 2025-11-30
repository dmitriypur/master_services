<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendTelegramReminderToClient;
use App\Models\Appointment;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendClientReminders extends Command
{
    protected $signature = 'app:send-client-reminders';

    protected $description = 'Отправка напоминаний клиентам за 1 час до начала';

    public function handle(): int
    {
        $from = Carbon::now();
        $to = Carbon::now()->addHour();

        $appointments = Appointment::query()
            ->where('status', 'planned')
            ->whereNull('reminder_for_client_sent_at')
            ->whereBetween('starts_at', [$from, $to])
            ->with(['client', 'service'])
            ->get();

        $dispatched = 0;
        foreach ($appointments as $appointment) {
            $client = $appointment->client;
            $channels = (array) ($client?->preferred_channels ?? []);
            if ($client && $client->telegram_id && in_array('telegram', $channels, true)) {
                SendTelegramReminderToClient::dispatch($appointment->id)->onQueue('default');
                $dispatched++;
            }
        }

        $this->info('Client reminders dispatched: '.$dispatched);

        return self::SUCCESS;
    }
}
