<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Master;

use App\Actions\Appointments\NotifyAppointmentAction;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentNotificationController extends Controller
{
    public function notify(Appointment $appointment, Request $request, NotifyAppointmentAction $action): JsonResponse
    {
        $user = $request->user();
        if (! $user || $appointment->master_id !== $user->id) {
            abort(404);
        }

        $data = $action->execute($appointment);

        return response()->json($data);
    }
}
