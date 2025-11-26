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
                $serviceIds = array_map('intval', $data['services']);
                $syncData = [];
                foreach ($serviceIds as $id) {
                    $syncData[$id] = ['is_active' => true];
                }
                $user->services()->sync($syncData);
            }
        });

        return $user->refresh();
    }
}
