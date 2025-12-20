<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Auth\GenerateMasterTokenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TelegramLoginRequest;
use App\Services\Telegram\TelegramWebAppService;
use Illuminate\Http\JsonResponse;

class TelegramLoginController extends Controller
{
    public function __invoke(TelegramLoginRequest $request, TelegramWebAppService $service, GenerateMasterTokenAction $action): JsonResponse
    {
        $validated = $request->validated();
        $userData = $service->validateLoginWidget($validated);

        if (empty($userData['id'])) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $token = $action->execute((string) $userData['id'], $userData);

        return response()->json(['token' => $token]);
    }
}
