<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Master\MasterCrmRequest;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;

class MasterCrmController extends Controller
{
    public function storeNotes(Appointment $appointment, MasterCrmRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        if ($appointment->master_id !== $userId) {
            abort(404);
        }

        $appointment->private_notes = (string) $request->input('private_notes');
        $appointment->save();

        return response()->json(['ok' => true]);
    }
}
