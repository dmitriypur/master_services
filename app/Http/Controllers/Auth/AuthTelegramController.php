<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TelegramWebAppAuthRequest;
use App\Actions\Auth\GenerateMasterTokenAction;
use App\Models\User;
use App\Services\Telegram\TelegramWebAppService;
use Illuminate\Http\JsonResponse;

class AuthTelegramController extends Controller
{
    public function store(TelegramWebAppAuthRequest $request, TelegramWebAppService $service, GenerateMasterTokenAction $action): JsonResponse
    {
        \Log::info('webapp-auth-start', [
            'ua' => $request->header('User-Agent'),
            'init_len' => strlen((string) $request->input('initData')),
        ]);
        $userData = $service->validateInitData($request->input('initData'));
        if (empty($userData['id'])) {
            \Log::warning('webapp-auth-invalid-init');

            return response()->json(['message' => 'Неверные данные WebApp'], 422);
        }

        $name = trim(($userData['first_name'] ?? '').' '.($userData['last_name'] ?? '')) ?: ($userData['username'] ?? ('tg_'.$userData['id']));
        $email = 'tg_'.$userData['id'].'@local';

        $token = $action->execute((string) $userData['id'], $userData);

        $user = User::query()
            ->where('telegram_id', (int) $userData['id'])
            ->where('role', 'master')
            ->firstOrFail();

        $redirect = url('/master/calendar');

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
            'redirect' => $redirect,
        ]);
    }
}
