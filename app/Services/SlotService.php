<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SlotService
{
    public function getSlotsForDate(User $master, Carbon $date): array
    {
        $settings = $master->masterSettings;

        if (! $settings) {
            return [];
        }

        $workDays = (array) ($settings->work_days ?? []);
        $dayCode = match ($date->dayOfWeekIso) {
            1 => 'mon',
            2 => 'tue',
            3 => 'wed',
            4 => 'thu',
            5 => 'fri',
            6 => 'sat',
            7 => 'sun',
        };

        if (! in_array($dayCode, $workDays, true)) {
            return [];
        }

        $timeFrom = $settings->work_time_from;
        $timeTo = $settings->work_time_to;
        $duration = (int) ($settings->slot_duration_min ?? 0);

        if (! $timeFrom || ! $timeTo || $duration <= 0) {
            return [];
        }

        $tz = config('app.timezone');
        $workStart = Carbon::parse($date->toDateString().' '.$timeFrom, $tz);
        $workEnd = Carbon::parse($date->toDateString().' '.$timeTo, $tz);

        if ($workStart->gte($workEnd)) {
            return [];
        }

        $appointments = DB::table('appointments')
            ->where('master_id', $master->id)
            ->where('status', 'planned')
            ->where('starts_at', '<', $workEnd)
            ->where('ends_at', '>', $workStart)
            ->get(['starts_at', 'ends_at']);

        $busy = [];
        foreach ($appointments as $a) {
            $busy[] = [Carbon::parse($a->starts_at), Carbon::parse($a->ends_at)];
        }

        $slots = [];
        $cursor = $workStart->copy();
        while ($cursor->lt($workEnd)) {
            $slotEnd = $cursor->copy()->addMinutes($duration);
            if ($slotEnd->gt($workEnd)) {
                break;
            }

            $overlaps = false;
            foreach ($busy as [$bStart, $bEnd]) {
                if ($bStart->lt($slotEnd) && $bEnd->gt($cursor)) {
                    $overlaps = true;
                    break;
                }
            }

            $slots[] = [
                'time' => $cursor->format('H:i'),
                'starts_at' => $cursor->format('Y-m-d H:i:s'),
                'available' => ! $overlaps,
            ];

            $cursor->addMinutes($duration);
        }

        return $slots;
    }

    public function isFree(User $master, Carbon $startsAt, Carbon $endsAt): bool
    {
        $settings = $master->masterSettings;
        if (! $settings) {
            return false;
        }

        $workDays = (array) ($settings->work_days ?? []);
        $dayCode = match ($startsAt->dayOfWeekIso) {
            1 => 'mon',
            2 => 'tue',
            3 => 'wed',
            4 => 'thu',
            5 => 'fri',
            6 => 'sat',
            7 => 'sun',
        };
        if (! in_array($dayCode, $workDays, true)) {
            return false;
        }

        $timeFrom = $settings->work_time_from;
        $timeTo = $settings->work_time_to;
        if (! $timeFrom || ! $timeTo) {
            return false;
        }

        $tz = config('app.timezone');
        $workStart = Carbon::parse($startsAt->toDateString().' '.$timeFrom, $tz);
        $workEnd = Carbon::parse($startsAt->toDateString().' '.$timeTo, $tz);
        if ($workStart->gte($workEnd)) {
            return false;
        }
        if ($startsAt->lt($workStart) || $endsAt->gt($workEnd)) {
            return false;
        }

        $exists = DB::table('appointments')
            ->where('master_id', $master->id)
            ->where('status', 'planned')
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        return ! $exists;
    }
}
