<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Master;

use App\Actions\Appointments\CreateAppointmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Appointments\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function store(StoreAppointmentRequest $request, CreateAppointmentAction $action): JsonResource
    {
        $appointment = $action->execute($request->validated());

        return new AppointmentResource($appointment);
    }

    public function showAt(Request $request): JsonResource
    {
        $master = $request->user();
        $date = (string) $request->query('date');
        $time = (string) $request->query('time');
        if ($date === '' || $time === '') {
            throw ValidationException::withMessages([
                'date' => ['date/time required'],
            ]);
        }

        $tz = config('app.timezone');
        $slotStart = Carbon::parse($date.' '.$time, $tz);
        $duration = (int) ($master->masterSettings?->slot_duration_min ?? 0);
        $slotEnd = $slotStart->copy()->addMinutes(max($duration, 1));

        $appointment = Appointment::query()
            ->where('master_id', $master->id)
            ->where('status', Appointment::STATUS_SCHEDULED)
            ->where('starts_at', '<', $slotEnd)
            ->where('ends_at', '>', $slotStart)
            ->with(['client', 'service'])
            ->firstOrFail();

        return new AppointmentResource($appointment);
    }
}
