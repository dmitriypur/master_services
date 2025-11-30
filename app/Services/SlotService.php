<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Appointment;
use App\Models\MasterScheduleException;
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

        $exceptions = MasterScheduleException::query()
            ->where('master_id', $master->id)
            ->where('date', $date->toDateString())
            ->get();

        $dayOff = $exceptions->firstWhere('type', 'day_off');
        if ($dayOff) {
            return [];
        }

        $override = $exceptions->firstWhere('type', 'override');

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

        $timeFrom = $override?->start_time ?? $settings->work_time_from;
        $timeTo = $override?->end_time ?? $settings->work_time_to;
        $duration = (int) ($settings->slot_duration_min ?? 0);

        if (! $override && ! in_array($dayCode, $workDays, true)) {
            return [];
        }

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
            ->where('status', Appointment::STATUS_SCHEDULED)
            ->where('starts_at', '<', $workEnd)
            ->where('ends_at', '>', $workStart)
            ->get(['starts_at', 'ends_at']);

        $busy = [];
        foreach ($appointments as $a) {
            $busy[] = [
                Carbon::parse((string) $a->starts_at)->timezone($tz),
                Carbon::parse((string) $a->ends_at)->timezone($tz),
            ];
        }

        foreach ($exceptions->where('type', 'break') as $ex) {
            if ($ex->start_time && $ex->end_time) {
                $bStart = Carbon::parse($date->toDateString().' '.$ex->start_time, $tz);
                $bEnd = Carbon::parse($date->toDateString().' '.$ex->end_time, $tz);
                if ($bStart->lt($bEnd)) {
                    $busy[] = [$bStart, $bEnd];
                }
            }
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

        $exceptions = MasterScheduleException::query()
            ->where('master_id', $master->id)
            ->where('date', $startsAt->toDateString())
            ->get();

        $dayOff = $exceptions->firstWhere('type', 'day_off');
        if ($dayOff) {
            return false;
        }

        $override = $exceptions->firstWhere('type', 'override');

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

        $timeFrom = $override?->start_time ?? $settings->work_time_from;
        $timeTo = $override?->end_time ?? $settings->work_time_to;
        if (! $override && ! in_array($dayCode, $workDays, true)) {
            return false;
        }
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

        $busy = [];

        $appointments = DB::table('appointments')
            ->where('master_id', $master->id)
            ->where('status', Appointment::STATUS_SCHEDULED)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->get(['starts_at', 'ends_at']);

        foreach ($appointments as $a) {
            $busy[] = [
                Carbon::parse((string) $a->starts_at)->timezone($tz),
                Carbon::parse((string) $a->ends_at)->timezone($tz),
            ];
        }

        foreach ($exceptions->where('type', 'break') as $ex) {
            if ($ex->start_time && $ex->end_time) {
                $bStart = Carbon::parse($startsAt->toDateString().' '.$ex->start_time, $tz);
                $bEnd = Carbon::parse($startsAt->toDateString().' '.$ex->end_time, $tz);
                if ($bStart->lt($bEnd)) {
                    $busy[] = [$bStart, $bEnd];
                }
            }
        }

        foreach ($busy as [$bStart, $bEnd]) {
            if ($bStart->lt($endsAt) && $bEnd->gt($startsAt)) {
                return false;
            }
        }

        return true;
    }
}
