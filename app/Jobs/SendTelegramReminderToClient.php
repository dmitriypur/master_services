<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\NotificationLog;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class SendTelegramReminderToClient implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $appointmentId) {}

    public function handle(TelegramBotService $telegram): void
    {
        $appointment = Appointment::query()
            ->with(['client', 'service'])
            ->find($this->appointmentId);

        if (! $appointment) {
            return;
        }

        if ($appointment->status !== Appointment::STATUS_SCHEDULED || $appointment->reminder_for_client_sent_at !== null) {
            return;
        }

        $client = $appointment->client;
        $channels = (array) ($client?->preferred_channels ?? []);
        if (! $client || ! $client->telegram_id || ! in_array('telegram', $channels, true)) {
            return;
        }

        $tz = config('app.timezone');
        $dt = $appointment->starts_at instanceof Carbon ? $appointment->starts_at->copy()->timezone($tz) : Carbon::parse((string) $appointment->starts_at, $tz);
        $serviceName = (string) ($appointment->service?->name ?? 'услуга');
        $text = 'Напоминание: '.$serviceName.' '.$dt->format('d.m.Y H:i');

        $ok = false;
        $error = null;
        $payload = [];
        try {
            $payload = $telegram->sendMessage((int) $client->telegram_id, $text);
            $ok = (bool) Arr::get($payload, 'ok', false);
            if (! $ok) {
                $error = (string) Arr::get($payload, 'description', 'send failed');
            }
        } catch (\Throwable $e) {
            $ok = false;
            $error = $e->getMessage();
        }

        NotificationLog::query()->create([
            'appointment_id' => $appointment->id,
            'channel' => 'telegram',
            'status' => $ok ? 'sent' : 'failed',
            'sent_at' => Carbon::now($tz),
            'error_message' => $error,
        ]);

        if ($ok) {
            $appointment->forceFill([
                'reminder_for_client_sent_at' => Carbon::now($tz),
            ])->save();
        }
    }
}
