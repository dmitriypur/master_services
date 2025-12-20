<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Master\MasterScheduleExceptionRequest;
use App\Models\MasterScheduleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterScheduleExceptionController extends Controller
{
    public function store(MasterScheduleExceptionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['master_id'] = $request->user()->id;

        // Prevent duplicates for day_off
        if ($data['type'] === 'day_off') {
            $exists = MasterScheduleException::query()
                ->where('master_id', $data['master_id'])
                ->where('date', $data['date'])
                ->where('type', 'day_off')
                ->exists();

            if ($exists) {
                return response()->json(['message' => 'Уже установлен выходной'], 422);
            }
        }

        $exception = MasterScheduleException::query()->create($data);

        return response()->json(['id' => $exception->id], 201);
    }

    public function update(MasterScheduleException $exception, MasterScheduleExceptionRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        if ((int) $exception->master_id !== (int) $userId) {
            return response()->json(['message' => 'Запрещено'], 403);
        }

        $data = $request->validated();
        $data['master_id'] = $userId;
        $exception->fill($data)->save();

        return response()->json(['ok' => true]);
    }

    public function destroy(MasterScheduleException $exception, Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        if ((int) $exception->master_id !== (int) $userId) {
            return response()->json(['message' => 'Запрещено'], 403);
        }
        $exception->delete();

        return response()->json(['ok' => true]);
    }
}
