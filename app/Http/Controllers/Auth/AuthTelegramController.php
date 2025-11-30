<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TelegramWebAppAuthRequest;
use App\Models\User;
use App\Services\Telegram\TelegramWebAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthTelegramController extends Controller
{
    public function store(TelegramWebAppAuthRequest $request, TelegramWebAppService $service): JsonResponse
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

        $user = User::query()
            ->where('telegram_id', $userData['id'])
            ->where('role', 'master')
            ->first();

        if (! $user) {
            return response()->json([
                'message' => 'Требуется регистрация мастера',
                'register_url' => url('/master/register'),
            ], 403);
        }

        Auth::login($user, true);
        \Log::info('webapp-auth-success', ['user_id' => $user->id]);

        $token = $user->createToken('telegram-token')->plainTextToken;

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
