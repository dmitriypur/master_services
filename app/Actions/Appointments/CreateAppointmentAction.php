<?php

declare(strict_types=1);

namespace App\Actions\Appointments;

use App\Actions\Client\CreateClient;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\User;
use App\Services\SlotService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateAppointmentAction
{
    public function __construct(
        private readonly SlotService $slots,
        private readonly CreateClient $createClient,
    ) {}

    public function execute(array $data): Appointment
    {
        $master = null;
        if (! empty($data['master_id'])) {
            $master = User::query()
                ->where('id', (int) $data['master_id'])
                ->whereIn('role', ['master', 'superadmin'])
                ->firstOrFail();
        } else {
            $userId = Auth::id();
            if ($userId === null) {
                throw ValidationException::withMessages([
                    'master_id' => ['Требуется авторизация'],
                ]);
            }
            $master = User::query()
                ->where('id', $userId)
                ->whereIn('role', ['master', 'superadmin'])
                ->firstOrFail();
        }

        $serviceId = (int) $data['service_id'];
        $hasService = $master->services()
            ->wherePivot('is_active', true)
            ->where('services.id', $serviceId)
            ->exists();
        if (! $hasService) {
            throw ValidationException::withMessages([
                'service_id' => ['Услуга недоступна для мастера'],
            ]);
        }

        $clientId = null;
        if (! empty($data['client_id'])) {
            $client = Client::query()
                ->where('id', (int) $data['client_id'])
                ->where('user_id', $master->id)
                ->first();
            if ($client === null) {
                throw ValidationException::withMessages([
                    'client_id' => ['Клиент не найден'],
                ]);
            }
            $clientId = $client->id;
        } else {
            // Пробуем найти клиента по телефону, если он передан
            $rawPhone = isset($data['client_phone']) ? (string) $data['client_phone'] : '';
            $digits = preg_replace('/\D+/', '', $rawPhone);
            if ($digits !== '') {
                if (strlen($digits) === 11 && str_starts_with($digits, '8')) {
                    $digits = '7'.substr($digits, 1);
                }
                if (strlen($digits) === 10) {
                    $digits = '7'.$digits;
                }
                if (strlen($digits) !== 11 || ! str_starts_with($digits, '7')) {
                    $digits = '';
                }
            }
            $phone = $digits !== '' ? $digits : null;

            if ($phone !== null) {
                $client = Client::query()
                    ->where('user_id', $master->id)
                    ->where('phone', $phone)
                    ->first();
            } else {
                $client = null;
            }

            if ($client) {
                $clientId = $client->id;
            } else {
                $client = $this->createClient->execute([
                    'user_id' => $master->id,
                    'name' => (string) $data['client_name'],
                    'phone' => $phone,
                    'preferred_channels' => $data['preferred_channels'] ?? [],
                ]);
                $clientId = $client->id;
            }
        }

        $tz = config('app.timezone');
        $startsAt = Carbon::parse(((string) $data['date']).' '.((string) $data['time']), $tz);

        $duration = (int) ($master->masterSettings?->slot_duration_min ?? 0);
        if ($duration <= 0) {
            throw ValidationException::withMessages([
                'time' => ['Неверная конфигурация слотов мастера'],
            ]);
        }

        $endsAt = $startsAt->copy()->addMinutes($duration);

        if (! $this->slots->isFree($master, $startsAt, $endsAt)) {
            throw ValidationException::withMessages([
                'time' => ['Слот занят'],
            ]);
        }

        $appointment = Appointment::query()->create([
            'master_id' => $master->id,
            'client_id' => $clientId,
            'service_id' => $serviceId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => Appointment::STATUS_SCHEDULED,
            'source' => ((string) ($data['source'] ?? 'manual')) === 'client' ? 'client' : 'manual',
        ]);

        return $appointment->refresh();
    }
}
