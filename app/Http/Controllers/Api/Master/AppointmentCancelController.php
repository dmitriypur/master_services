<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AppointmentCancelController extends Controller
{
    public function cancel(Appointment $appointment): JsonResponse
    {
        abort_unless($appointment->master_id === Auth::id(), 404);

        if ($appointment->status === Appointment::STATUS_SCHEDULED) {
            $appointment->status = Appointment::STATUS_CANCELED;
            $appointment->save();
        }

        return response()->json(['ok' => true]);
    }
}
