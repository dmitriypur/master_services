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

        $data = $slots->getSlotsForDate($user, $date);

        return SlotResource::collection($data)->response();
    }
}
