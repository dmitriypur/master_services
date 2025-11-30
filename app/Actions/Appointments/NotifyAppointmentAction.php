<?php

declare(strict_types=1);

namespace App\Actions\Appointments;

use App\Models\Appointment;
use App\Models\NotificationLog;
use App\Services\Telegram\TelegramBotService;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class NotifyAppointmentAction
{
    public function __construct(private readonly TelegramBotService $telegram) {}

    public function execute(Appointment $appointment): array
    {
        $appointment->loadMissing(['client', 'service', 'master']);

        $client = $appointment->client;
        $channels = (array) ($client?->preferred_channels ?? []);

        $tz = config('app.timezone');
        $dt = $appointment->starts_at instanceof Carbon ? $appointment->starts_at->copy()->timezone($tz) : Carbon::parse((string) $appointment->starts_at, $tz);
        $serviceName = (string) ($appointment->service?->name ?? 'услуга');
        $text = 'Напоминание: '.$serviceName.' '.$dt->format('d.m.Y H:i');

        $result = [
            'telegram' => null,
            'whatsapp_url' => null,
        ];

        if ($client && in_array('telegram', $channels, true) && $client->telegram_id) {
            $ok = false;
            $error = null;
            $payload = [];
            try {
                $payload = $this->telegram->sendMessage((int) $client->telegram_id, $text);
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

            $result['telegram'] = [
                'ok' => $ok,
            ];
        }

        if ($client && in_array('whatsapp', $channels, true)) {
            $phoneRaw = (string) ($client->whatsapp_phone ?? $client->phone ?? '');
            $digits = preg_replace('/\D+/', '', $phoneRaw) ?? '';
            $url = $digits !== '' ? ('https://wa.me/'.$digits.'?text='.urlencode($text)) : null;

            NotificationLog::query()->create([
                'appointment_id' => $appointment->id,
                'channel' => 'whatsapp',
                'status' => $url ? 'prepared' : 'failed',
                'sent_at' => null,
                'error_message' => $url ? null : 'phone missing',
            ]);

            $result['whatsapp_url'] = $url;
        }

        return $result;
    }
}
