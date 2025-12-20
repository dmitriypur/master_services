<?php

declare(strict_types=1);

namespace App\Actions\Master;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateSettingsAction
{
    public function execute(User $user, array $data): User
    {
        DB::transaction(function () use ($user, $data) {
            if (array_key_exists('city_id', $data)) {
                $user->city_id = (int) $data['city_id'];
                $user->save();
            }

            if (array_key_exists('phone', $data) && is_string($data['phone'])) {
                $digits = preg_replace('/\D+/', '', (string) $data['phone']) ?? '';
                if ($digits !== '' && strlen($digits) >= 5 && strlen($digits) <= 11) {
                    $user->phone = $digits;
                    $user->save();
                }
            }

            $payload = Arr::only($data, [
                'address',
                'work_days',
                'work_time_from',
                'work_time_to',
                'slot_duration_min',
            ]);

            $user->masterSettings()->updateOrCreate(
                ['user_id' => $user->id],
                $payload
            );

            if (array_key_exists('services', $data) && is_array($data['services'])) {
                $syncData = [];
                foreach ($data['services'] as $row) {
                    if (! is_array($row) || ! array_key_exists('id', $row)) {
                        continue;
                    }

                    $serviceId = (int) $row['id'];
                    if ($serviceId <= 0) {
                        continue;
                    }

                    $price = array_key_exists('price', $row) && $row['price'] !== null ? (int) $row['price'] : null;
                    $durationMinutes = array_key_exists('duration', $row) && $row['duration'] !== null ? (int) $row['duration'] : 60;
                    $durationMinutes = max($durationMinutes, 1);

                    $syncData[$serviceId] = [
                        'is_active' => true,
                        'price' => $price,
                        'duration_minutes' => $durationMinutes,
                    ];
                }
                $user->services()->sync($syncData);
            }

            // Проверяем, что есть хотя бы одна услуга перед активацией
            $hasServices = $user->services()->wherePivot('is_active', true)->exists();

            if (! $user->is_active && $hasServices) {
                $user->is_active = true;
                $user->profile_completed_at = now();
                $user->save();
            } elseif ($user->is_active && ! $hasServices) {
                // Если мастер удалил все услуги, деактивируем его
                $user->is_active = false;
                $user->save();
            }
        });

        return $user->refresh();
    }
}
