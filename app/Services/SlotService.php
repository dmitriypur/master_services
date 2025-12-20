<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Appointment;
use App\Models\MasterScheduleException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SlotService
{
    public function getSlotsForDate(User $master, Carbon $date, ?int $serviceId = null): array
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
            return [
                'is_day_off' => true,
                'day_off_id' => $dayOff->id,
                'slots' => [],
            ];
        }

        $override = $exceptions->firstWhere('type', 'override');

        $workDays = (array) ($settings->work_days ?? []);
        $dayCode = $date->dayOfWeekIso; // 1 (Mon) - 7 (Sun)

        $timeFrom = $override?->start_time ?? $settings->work_time_from;
        $timeTo = $override?->end_time ?? $settings->work_time_to;

        // Default step from settings or 15 mins
        $step = $settings->slot_duration_min > 0 ? $settings->slot_duration_min : 15;

        // Determine duration
        // If serviceId is provided, use its duration.
        // If NOT provided (view mode), use $step as duration to show blocks status.
        $duration = $step; 
        if ($serviceId) {
            $service = $master->services()
                ->where('services.id', $serviceId)
                ->first();
            if ($service && $service->pivot->duration_minutes > 0) {
                $duration = (int) $service->pivot->duration_minutes;
                // We do NOT change step here. 
                // We want to see if the service fits in the grid defined by $step.
                // E.g. Grid 15 min, Service 60 min.
                // Slots: 9:00 (check 60m), 9:15 (check 60m), 9:30 (check 60m)...
            }
        }

        Log::info('SlotService:getSlotsForDate', [
            'user_id' => $master->id,
            'date' => $date->toDateString(),
            'service_id' => $serviceId,
            'duration' => $duration,
            'day_code' => $dayCode,
            'work_days' => $workDays,
            'time_from' => $timeFrom,
            'time_to' => $timeTo,
        ]);

        if (! $override && ! in_array($dayCode, $workDays, true)) {
            return [];
        }

        if (! $timeFrom || ! $timeTo || $duration <= 0) {
            return [];
        }

        $tz = $settings->timezone ?? config('app.timezone');
        $workStart = Carbon::parse($date->toDateString().' '.$timeFrom, $tz);
        $workEnd = Carbon::parse($date->toDateString().' '.$timeTo, $tz);

        if ($workStart->gte($workEnd)) {
            return [];
        }

        // Fetch appointments with relations
        $appointments = Appointment::query()
            ->with(['client', 'service'])
            ->where('master_id', $master->id)
            ->where('status', Appointment::STATUS_SCHEDULED)
            ->where('starts_at', '<', $workEnd)
            ->where('ends_at', '>', $workStart)
            ->get();

        $busy = [];
        foreach ($appointments as $a) {
            $busy[] = [
                'start' => Carbon::parse((string) $a->starts_at)->timezone($tz),
                'end' => Carbon::parse((string) $a->ends_at)->timezone($tz),
                'type' => 'appointment',
                'id' => $a->id,
                'client' => $a->client,
                'service' => $a->service,
            ];
        }

        foreach ($exceptions->where('type', 'break') as $ex) {
            if ($ex->start_time && $ex->end_time) {
                $bStart = Carbon::parse($date->toDateString().' '.$ex->start_time, $tz);
                $bEnd = Carbon::parse($date->toDateString().' '.$ex->end_time, $tz);
                if ($bStart->lt($bEnd)) {
                    $busy[] = [
                        'start' => $bStart,
                        'end' => $bEnd,
                        'type' => 'break',
                        'id' => $ex->id,
                    ];
                }
            }
        }

        $slots = [];
        $cursor = $workStart->copy();
        $now = Carbon::now($tz);

        // When viewing (serviceId=null), we iterate with $step.
        // When booking (serviceId!=null), we still iterate with $step to find start times?
        // Usually booking systems show slots every X mins (step).
        
        while ($cursor->copy()->addMinutes($duration)->lte($workEnd)) {
            $slotStart = $cursor->copy();
            $slotEnd = $cursor->copy()->addMinutes($duration);

            $overlaps = false;
            $breakId = null;
            $appointmentData = null;

            foreach ($busy as $b) {
                $bStart = $b['start'];
                $bEnd = $b['end'];

                // Check intersection: (StartA < EndB) and (EndA > StartB)
                if ($bStart->lt($slotEnd) && $bEnd->gt($slotStart)) {
                    $overlaps = true;
                    if (($b['type'] ?? '') === 'break') {
                        $breakId = $b['id'] ?? null;
                    }
                    if (($b['type'] ?? '') === 'appointment') {
                        $appointmentData = [
                            'id' => $b['id'] ?? null,
                            'client' => $b['client'] ?? null,
                            'service' => $b['service'] ?? null,
                        ];
                    }
                    // For visualization, if we find an appointment, we definitely want to show it.
                    // If we find a break, we show it.
                    // We can break early if we just want "is busy", but since we want to show WHAT is there,
                    // we might need to prioritize. Usually appointment overrides break? Or they shouldn't coexist.
                    // Let's assume appointment is most important.
                    if ($appointmentData) {
                        break; 
                    }
                }
            }

            $isPast = $slotStart->lt($now);

            $slots[] = [
                'time' => $slotStart->format('H:i'),
                'starts_at' => $slotStart->format('Y-m-d H:i:s'),
                'available' => ! $overlaps,
                'break_id' => $breakId,
                'appointment' => $appointmentData,
                'is_past' => $isPast,
            ];

            $cursor->addMinutes($step);
        }

        return [
            'is_day_off' => false,
            'day_off_id' => null,
            'slots' => $slots,
        ];
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
        $dayCode = $startsAt->dayOfWeekIso;

        $timeFrom = $override?->start_time ?? $settings->work_time_from;
        $timeTo = $override?->end_time ?? $settings->work_time_to;
        if (! $override && ! in_array($dayCode, $workDays, true)) {
            return false;
        }

        if (! $timeFrom || ! $timeTo) {
            return false;
        }
        
        $tz = $settings->timezone ?? config('app.timezone');
        $workStart = Carbon::parse($startsAt->toDateString().' '.$timeFrom, $tz);
        $workEnd = Carbon::parse($startsAt->toDateString().' '.$timeTo, $tz);

        if ($startsAt->lt($workStart) || $endsAt->gt($workEnd)) {
            return false;
        }

        // Check appointments overlap
        $exists = Appointment::query()
            ->where('master_id', $master->id)
            ->where('status', Appointment::STATUS_SCHEDULED)
            ->where(function ($q) use ($startsAt, $endsAt) {
                $q->where('starts_at', '<', $endsAt)
                  ->where('ends_at', '>', $startsAt);
            })
            ->exists();

        if ($exists) {
            return false;
        }

        // Check breaks overlap
        foreach ($exceptions->where('type', 'break') as $ex) {
            if ($ex->start_time && $ex->end_time) {
                $bStart = Carbon::parse($startsAt->toDateString().' '.$ex->start_time, $tz);
                $bEnd = Carbon::parse($startsAt->toDateString().' '.$ex->end_time, $tz);
                if ($bStart->lt($endsAt) && $bEnd->gt($startsAt)) {
                    return false;
                }
            }
        }

        return true;
    }
}
