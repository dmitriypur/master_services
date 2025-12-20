<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Http\Resources\SlotResource;
use App\Models\User;
use App\Services\SlotService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterSlotController extends Controller
{
    public function index(int $master, Request $request, SlotService $slots): JsonResponse
    {
        $user = User::query()->findOrFail($master);

        $dateParam = (string) $request->query('date', Carbon::now()->toDateString());
        $date = Carbon::parse($dateParam);
        $serviceId = $request->query('service_id') ? (int) $request->query('service_id') : null;

        $result = $slots->getSlotsForDate($user, $date, $serviceId);

        return response()->json([
            'data' => SlotResource::collection($result['slots'] ?? $result),
            'meta' => [
                'is_day_off' => $result['is_day_off'] ?? false,
                'day_off_id' => $result['day_off_id'] ?? null,
            ],
        ]);
    }
}
